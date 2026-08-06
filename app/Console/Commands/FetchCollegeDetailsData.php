<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\College;
use App\Models\Course;
use App\Models\Subject;
use App\Models\CollegeSubject;

class FetchCollegeDetailsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-colleges-details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch detailed data for colleges by ID and normalize subjects/courses';

    private string $cookie = 'XSRF-TOKEN=eyJpdiI6ImZoQ1RDKzFIbXk0cVRuTW5jREU1QlE9PSIsInZhbHVlIjoiTGoyQ0RZandLaDA5MXdvQi9veEdSRk5iL0JYcUttSytqUmtNbkt4cFhMTEpGaElKZHBRNWI0R1FQUWFTVUtzd28yclVldEUwMitZOTVtUXVKWlhCOVBGK1AzWldZUW1EME1FbE1oUDBDTnEyd2hNOERaM0ppRVVLR1NsZ1pTdWIiLCJtYWMiOiI0NDllNDQzOTI4NTM0YTNkYTdlNmY3MzQ3ZjIyMTgwZjRjMjA3NTJiZTc1YTFhOWQxNDVjOTdlOWNmOWFlNzRkIiwidGFnIjoiIn0%3D; expires=Wed, 05 Aug 2026 12:47:55 GMT; Max-Age=7200; path=/; secure; samesite=lax';
    private string $xsrfToken = 'eyJpdiI6IlNHcGNmdVNpTzA2ZVYrRHlNcmhEVEE9PSIsInZhbHVlIjoibVRYeHZmMU5kQkRuVlhRa3ZJM1RMMmE4MWE1U2hybWpIcUdHUERESDZwV2FNS0xHSGVPZ1VCcHZyOWJCOUhneXZ0Z0h6ejhEWjFDOFNtWTRyNnQ3eFFYOEhDQ2NkODAxRGZxNXFHMjlBMk0xRUpZMmpHVndpWTJlS1paNWRSV1IiLCJtYWMiOiJhYWQ3Njc1ZGVlOTg2ZmE1YTgwNjI3YjIxNGUwNDc3ODRkYjk3OTcwODc4ZTY3YjBlZjQ4OWZmYjE1OTQwMDk1IiwidGFnIjoiIn0';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("ধাপ ১: ওয়েবসাইট থেকে ফ্রেশ সেশন এবং টোকেন সংগ্রহ করা হচ্ছে...");

        // ১. তাজা কুকি সংগ্রহ করা
        try {
            $initialResponse = Http::get('https://collegeportal.nu.ac.bd');
            $cookies = $initialResponse->cookies();

            $xsrfCookie = $cookies->getCookieByName('XSRF-TOKEN');
            $sessionCookie = $cookies->getCookieByName('college_portal_session');

            if ($xsrfCookie && $sessionCookie) {
                $this->xsrfToken = urldecode($xsrfCookie->getValue());
                $this->cookie = "XSRF-TOKEN=" . $xsrfCookie->getValue() . "; college_portal_session=" . $sessionCookie->getValue();
                $this->info("টোকেন সফলভাবে সংগ্রহ করা হয়েছে!");
            } else {
                $this->error("টোকেন সংগ্রহ করতে ব্যর্থ। সার্ভার হয়তো ব্লক করেছে অথবা সাইট ডাউন আছে।");
                return;
            }
        } catch (\Exception $e) {
            $this->error("ওয়েবসাইটে কানেক্ট করতে সমস্যা হচ্ছে: " . $e->getMessage());
            return;
        }

        // ২. ডাটাবেস থেকে সেভ করা কলেজগুলো বের করা (যাদের nu_id আছে)
        $colleges = College::whereNotNull('nu_id')->get();
        $total = $colleges->count();

        if ($total == 0) {
            $this->warn("ডাটাবেসে কোনো কলেজ পাওয়া যায়নি! আগে লিস্ট সেভ করুন।");
            return;
        }

        $this->info("মোট $total টি কলেজের বিস্তারিত তথ্য লোড করা শুরু হচ্ছে...\n");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // ৩. লুপ চালিয়ে প্রতিটি কলেজের আইডি অনুযায়ী ডিটেইলস আনা
        foreach ($colleges as $college) {
            $nuId = $college->nu_id;
            $code = $college->college_code;

            try {
                // asForm() ব্যবহার করে Form Data হিসেবে ID পাঠানো হচ্ছে
                $detailResponse = Http::asForm()
                    ->withHeaders($this->getHeaders())
                    ->post('https://collegeportal.nu.ac.bd/college-list-view-id', [
                        'id' => $nuId
                    ]);

                if ($detailResponse->successful()) {
                    $detailData = $detailResponse->json();

                    // JSON রেসপন্সে college ডাটা থাকলে ডাটাবেস আপডেট করা
                    if (isset($detailData['college'])) {
                        $this->updateCollegeData($college, $detailData);
                    } else {
                        Log::warning("No detailed data found for College Code: $code");
                    }
                } else {
                    Log::warning("Failed to get details for College Code: $code, Status: " . $detailResponse->status());
                }
            } catch (\Exception $e) {
                // টার্মিনালে এরর দেখানোর জন্য
                $this->error("\nCollege Code {$code} এর ডাটা সেভ করতে এরর: " . $e->getMessage());
                Log::error("Error fetching data for college ID $nuId : " . $e->getMessage());
            }

            // সার্ভারে ব্লক হওয়া এড়াতে প্রতি রিকোয়েস্টের পর ১ সেকেন্ড বিরতি
            sleep(1);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("সফলভাবে সমস্ত কলেজের বিস্তারিত তথ্য এবং কোর্স/সাবজেক্ট ডাটাবেসে আপডেট করা হয়েছে!");
    }

    /**
     * API রিকোয়েস্টের জন্য প্রয়োজনীয় হেডার্স
     */
    private function getHeaders(): array
    {
        return [
            'Accept'           => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
            'X-XSRF-TOKEN'     => $this->xsrfToken,
            'Cookie'           => $this->cookie,
            'User-Agent'       => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'Origin'           => 'https://collegeportal.nu.ac.bd',
            'Referer'          => 'https://collegeportal.nu.ac.bd/college-list'
        ];
    }

    /**
     * ডাটাবেস আপডেট করার লজিক (Normalization সহ)
     */
    private function updateCollegeData(College $college, array $data): void
    {
        $collegeInfo = $data['college'];
        $details = $collegeInfo['college_user_detail'] ?? [];

        // ১. College টেবিল আপডেট করা
        $college->update([
            'college_name_bn' => $collegeInfo['college_name_bn'] ?? $college->college_name_bn,
            'principal_name'  => $details['principal_name'] ?? $college->principal_name,
            'college_phone'   => $details['college_phone'] ?? $college->college_phone,
            'college_mobile'  => $details['college_mobile'] ?? $college->college_mobile,
            'address'         => $details['address'] ?? $college->address,
            'upazilla'        => $details['upazilla'] ?? $college->upazilla,
            'eiin'            => $details['eiin'] ?? $college->eiin,
            'establish_year'  => $details['establish_Year'] ?? $college->establish_year,
            'lat'             => $details['lat'] ?? $college->lat,
            'lng'             => $details['lng'] ?? $college->lng,
        ]);

        // ২. Courses, Subjects এবং CollegeSubjects আপডেট বা তৈরি করা
        if (isset($data['collegeSubjectLists']) && is_array($data['collegeSubjectLists'])) {
            foreach ($data['collegeSubjectLists'] as $item) {

                // Course টেবিলে কোর্স তৈরি বা খুঁজে বের করা
                $course = Course::firstOrCreate(
                    ['course_code' => $item['course_code']],
                    ['course_name' => $item['course_name'] ?? null]
                );

                // Subject টেবিলে সাবজেক্ট তৈরি বা খুঁজে বের করা
                $subject = Subject::firstOrCreate(
                    ['subject_code' => $item['subject_code']],
                    ['subject_name' => $item['subject_name'] ?? null]
                );

                // ডাটা ক্লিনিং (ফাঁকা স্ট্রিং আসলে null করে দেওয়া)
                $seatCapacity = (isset($item['seat_capacity']) && trim($item['seat_capacity']) !== '')
                    ? $item['seat_capacity']
                    : null;

                $affDate = (isset($item['aff_date']) && !in_array($item['aff_date'], ['0000-00-00', '']))
                    ? $item['aff_date']
                    : null;

                // Pivot/রিলেশনশিপ টেবিলে ডাটা সেভ করা
                CollegeSubject::updateOrCreate(
                    [
                        'college_id' => $college->id,
                        'course_id'  => $course->id,
                        'subject_id' => $subject->id,
                    ],
                    [
                        'seat_capacity' => $seatCapacity,
                        'aff_date'      => $affDate,
                    ]
                );
            }
        }
    }
}
