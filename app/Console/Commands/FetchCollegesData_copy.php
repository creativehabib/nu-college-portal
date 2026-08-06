<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\College;
use App\Models\CollegeSubject;

class FetchCollegesData_copy extends Command
{
    protected $signature = 'app:fetch-colleges';
    protected $description = 'Fetch all colleges list and details from NU API';

    // আপনার ব্রাউজারের Network ট্যাব থেকে কপি করে এখানে বসান
    private string $cookie = 'XSRF-TOKEN=eyJpdiI6ImZoQ1RDKzFIbXk0cVRuTW5jREU1QlE9PSIsInZhbHVlIjoiTGoyQ0RZandLaDA5MXdvQi9veEdSRk5iL0JYcUttSytqUmtNbkt4cFhMTEpGaElKZHBRNWI0R1FQUWFTVUtzd28yclVldEUwMitZOTVtUXVKWlhCOVBGK1AzWldZUW1EME1FbE1oUDBDTnEyd2hNOERaM0ppRVVLR1NsZ1pTdWIiLCJtYWMiOiI0NDllNDQzOTI4NTM0YTNkYTdlNmY3MzQ3ZjIyMTgwZjRjMjA3NTJiZTc1YTFhOWQxNDVjOTdlOWNmOWFlNzRkIiwidGFnIjoiIn0%3D; expires=Wed, 05 Aug 2026 12:47:55 GMT; Max-Age=7200; path=/; secure; samesite=lax';
    private string $xsrfToken = 'eyJpdiI6IlNHcGNmdVNpTzA2ZVYrRHlNcmhEVEE9PSIsInZhbHVlIjoibVRYeHZmMU5kQkRuVlhRa3ZJM1RMMmE4MWE1U2hybWpIcUdHUERESDZwV2FNS0xHSGVPZ1VCcHZyOWJCOUhneXZ0Z0h6ejhEWjFDOFNtWTRyNnQ3eFFYOEhDQ2NkODAxRGZxNXFHMjlBMk0xRUpZMmpHVndpWTJlS1paNWRSV1IiLCJtYWMiOiJhYWQ3Njc1ZGVlOTg2ZmE1YTgwNjI3YjIxNGUwNDc3ODRkYjk3OTcwODc4ZTY3YjBlZjQ4OWZmYjE1OTQwMDk1IiwidGFnIjoiIn0';

    public function handle()
    {
        $this->info("Fetching fresh session cookies...");

        // ১. প্রথমে মূল পেজে একটি GET রিকোয়েস্ট পাঠিয়ে তাজা কুকি সংগ্রহ করা
        $initialResponse = \Illuminate\Support\Facades\Http::get('https://collegeportal.nu.ac.bd');
        $cookies = $initialResponse->cookies();

        $xsrfCookie = $cookies->getCookieByName('XSRF-TOKEN');
        $sessionCookie = $cookies->getCookieByName('college_portal_session');

        if ($xsrfCookie && $sessionCookie) {
            // X-XSRF-TOKEN হেডারে পাঠানোর জন্য ভ্যালুটি urldecode করে নিতে হয়
            $this->xsrfToken = urldecode($xsrfCookie->getValue());
            $this->cookie = "XSRF-TOKEN=" . $xsrfCookie->getValue() . "; college_portal_session=" . $sessionCookie->getValue();
            $this->info("Tokens fetched successfully! Now fetching college list...");
        } else {
            $this->warn("Could not auto-fetch cookies. Using hardcoded ones...");
        }

        // ২. সব কলেজের বেসিক লিস্ট আনা
        $listResponse = Http::withHeaders($this->getHeaders())
            ->post('https://collegeportal.nu.ac.bd/college-list-data');

        if (!$listResponse->successful()) {
            $this->error("Failed to fetch college list. Status: " . $listResponse->status());
            return;
        }

        $colleges = $listResponse->json();
        $total = count($colleges);
        $this->info("Total colleges found: $total");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // ২. লুপ চালিয়ে প্রতিটি কলেজের ডিটেইলস আনা
        foreach ($colleges as $basicCollege) {
            $nuId = $basicCollege['id'];
            $code = $basicCollege['college_code'];

            try {
                $detailResponse = Http::withHeaders($this->getHeaders())
                    ->post('https://collegeportal.nu.ac.bd/college-list-view-id', [
                        'id' => $nuId
                    ]);

                if ($detailResponse->successful()) {
                    $detailData = $detailResponse->json();
                    $this->saveCollegeData($detailData, $nuId);
                } else {
                    Log::warning("Failed to get details for College Code: $code");
                }
            } catch (\Exception $e) {
                Log::error("Error fetching data for college ID $nuId : " . $e->getMessage());
            }

            // সার্ভারে ব্লক হওয়া এড়াতে প্রতি রিকোয়েস্টের পর ১ সেকেন্ড বিরতি
            sleep(1);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully fetched and saved all college data!");
    }

    /**
     * API রিকোয়েস্টের জন্য প্রয়োজনীয় হেডার্স
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-XSRF-TOKEN' => $this->xsrfToken,
            'Cookie' => $this->cookie,
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        ];
    }

    /**
     * ডাটাবেসে সেভ করার লজিক
     */
    private function saveCollegeData(array $data, int $nuId): void
    {
        if (!isset($data['college'])) return;

        $collegeInfo = $data['college'];
        $details = $collegeInfo['college_user_detail'] ?? [];

        // College Update or Create
        $college = College::updateOrCreate(
            ['college_code' => $collegeInfo['college_code']],
            [
                'nu_id' => $nuId,
                'college_name' => $collegeInfo['college_name'] ?? null,
                'college_name_bn' => $collegeInfo['college_name_bn'] ?? null,
                'email' => $collegeInfo['email'] ?? null,
                'principal_name' => $details['principal_name'] ?? null,
                'college_phone' => $details['college_phone'] ?? null,
                'college_mobile' => $details['college_mobile'] ?? null,
                'address' => $details['address'] ?? null,
                'upazilla' => $details['upazilla'] ?? null,
                'eiin' => $details['eiin'] ?? null,
                'establish_year' => $details['establish_Year'] ?? null,
                'lat' => $details['lat'] ?? null,
                'lng' => $details['lng'] ?? null,
            ]
        );

        // Subjects Update or Create
        if (isset($data['collegeSubjectLists']) && is_array($data['collegeSubjectLists'])) {
            foreach ($data['collegeSubjectLists'] as $subject) {
                CollegeSubject::updateOrCreate(
                    [
                        'college_id' => $college->id,
                        'course_code' => $subject['course_code'],
                        'subject_code' => $subject['subject_code'],
                    ],
                    [
                        'course_name' => $subject['course_name'] ?? null,
                        'subject_name' => $subject['subject_name'] ?? null,
                        'seat_capacity' => $subject['seat_capacity'] ?? null,
                    ]
                );
            }
        }
    }
}
