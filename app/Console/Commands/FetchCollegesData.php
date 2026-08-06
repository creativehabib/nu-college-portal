<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\College;
use App\Models\CollegeUserDetail;
use App\Models\Course;
use App\Models\Subject;
use App\Models\CollegeSubject;

class FetchCollegesData extends Command
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
    protected $description = 'Fetch detailed data for colleges by ID and normalize subjects/courses into new schema';

    // আপনার ব্রাউজারের Network ট্যাব থেকে কপি করে এখানে বসান
    private string $cookie = 'XSRF-TOKEN=eyJpdiI6ImZoQ1RDKzFIbXk0cVRuTW5jREU1QlE9PSIsInZhbHVlIjoiTGoyQ0RZandLaDA5MXdvQi9veEdSRk5iL0JYcUttSytqUmtNbkt4cFhMTEpGaElKZHBRNWI0R1FQUWFTVUtzd28yclVldEUwMitZOTVtUXVKWlhCOVBGK1AzWldZUW1EME1FbE1oUDBDTnEyd2hNOERaM0ppRVVLR1NsZ1pTdWIiLCJtYWMiOiI0NDllNDQzOTI4NTM0YTNkYTdlNmY3MzQ3ZjIyMTgwZjRjMjA3NTJiZTc1YTFhOWQxNDVjOTdlOWNmOWFlNzRkIiwidGFnIjoiIn0%3D; expires=Wed, 05 Aug 2026 12:47:55 GMT; Max-Age=7200; path=/; secure; samesite=lax';
    private string $xsrfToken = 'eyJpdiI6IlNHcGNmdVNpTzA2ZVYrRHlNcmhEVEE9PSIsInZhbHVlIjoibVRYeHZmMU5kQkRuVlhRa3ZJM1RMMmE4MWE1U2hybWpIcUdHUERESDZwV2FNS0xHSGVPZ1VCcHZyOWJCOUhneXZ0Z0h6ejhEWjFDOFNtWTRyNnQ3eFFYOEhDQ2NkODAxRGZxNXFHMjlBMk0xRUpZMmpHVndpWTJlS1paNWRSV1IiLCJtYWMiOiJhYWQ3Njc1ZGVlOTg2ZmE1YTgwNjI3YjIxNGUwNDc3ODRkYjk3OTcwODc4ZTY3YjBlZjQ4OWZmYjE1OTQwMDk1IiwidGFnIjoiIn0';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("ধাপ ১: ওয়েবসাইট থেকে ফ্রেশ সেশন এবং টোকেন সংগ্রহ করা হচ্ছে...");

        // ১. তাজা কুকি সংগ্রহ করা
        try {
            $initialResponse = Http::get('https://collegeportal.nu.ac.bd');
            $cookies = $initialResponse->cookies();

            $xsrfCookie = $cookies->getCookieByName('XSRF-TOKEN');
            $sessionCookie = $cookies->getCookieByName('college_portal_session');

            if ($xsrfCookie && $sessionCookie) {
                $this->xsrfToken = urldecode($xsrfCookie->getValue());
                $this->cookie = "XSRF-TOKEN=" . $xsrfCookie->getValue() . "; college_portal_session=" . $sessionCookie->getValue();
                $this->info("টোকেন সফলভাবে সংগ্রহ করা হয়েছে!");
            } else {
                $this->error("টোকেন সংগ্রহ করতে ব্যর্থ। সার্ভার হয়তো ব্লক করেছে অথবা সাইট ডাউন আছে।");
                return;
            }
        } catch (\Exception $e) {
            $this->error("কানেকশন এরর: " . $e->getMessage());
            return;
        }

        // ২. ডাটাবেস থেকে সেভ করা কলেজগুলো বের করা (যাদের nu_id আছে)
        $colleges = College::whereNotNull('nu_id')->get();
        $total = $colleges->count();

        if ($total == 0) {
            $this->warn("ডাটাবেসে কোনো কলেজ পাওয়া যায়নি! আগে লিস্ট সেভ করুন।");
            return;
        }

        $this->info("মোট $total টি কলেজের বিস্তারিত তথ্য লোড করা শুরু হচ্ছে...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        // ৩. লুপ চালিয়ে প্রতিটি কলেজের আইডি অনুযায়ী ডিটেইলস আনা
        foreach ($colleges as $college) {
            $nuId = $college->nu_id; // API এর ID
            $code = $college->college_code;

            try {
                // asForm() এর পরিবর্তে JSON ফরম্যাটে ডাটা এবং collcode পাঠানো হচ্ছে
                $detailResponse = Http::withHeaders($this->getHeaders())
                    ->post('https://collegeportal.nu.ac.bd/college-list-view-id', [
                        'id'       => $nuId,
                        'collcode' => $code
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
                Log::error("Error fetching data for college ID $nuId : " . $e->getMessage());
            }

            // সার্ভারে ব্লক হওয়া এড়াতে প্রতি রিকোয়েস্টের পর ১ সেকেন্ড বিরতি
            sleep(1);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("সফলভাবে সমস্ত কলেজের বিস্তারিত তথ্য এবং কোর্স/সাবজেক্ট ডাটাবেসে আপডেট করা হয়েছে!");
    }

    /**
     * API রিকোয়েস্টের জন্য প্রয়োজনীয় হেডার্স
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type'     => 'application/json', // JSON ফরম্যাট
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
     * ডাটাবেস আপডেট করার লজিক (New Normalized Schema)
     */
    private function updateCollegeData(College $college, array $data): void
    {
        $collegeInfo = $data['college'];

        // ১. মূল 'colleges' টেবিলে শুধুমাত্র বেসিক ডাটা আপডেট করা
        $college->update([
            'college_name'      => $collegeInfo['college_name'] ?? $college->college_name,
            'college_name_bn'   => $collegeInfo['college_name_bn'] ?? $college->college_name_bn,
            'college_code'      => $collegeInfo['college_code'] ?? $college->college_code,
            'email'             => $collegeInfo['email'] ?? $college->email,
            'alt_email'         => $collegeInfo['alt_email'] ?? $college->alt_email,
            'role'              => $collegeInfo['role'] ?? $college->role,
            'approved'          => $collegeInfo['approved'] ?? $college->approved,
            'otp'               => $collegeInfo['otp'] ?? $college->otp,
            'password'          => $collegeInfo['password'] ?? $college->password,
            'email_verified_at' => $collegeInfo['email_verified_at'] ?? $college->email_verified_at,
            'remember_token'    => $collegeInfo['remember_token'] ?? $college->remember_token,
        ]);

        // ২. বাকি বিস্তারিত তথ্য 'college_user_details' টেবিলে সেভ করা
        $details = $collegeInfo['college_user_detail'] ?? [];
        if (!empty($details)) {
            CollegeUserDetail::updateOrCreate(
                ['college_id' => $college->id],
                [
                    'user_id'               => $details['user_id'] ?? null,
                    'principal_name'        => $details['principal_name'] ?? null,
                    'v_principal_name'      => $details['v_principal_name'] ?? null,
                    'clerkname'             => $details['clerkname'] ?? null,
                    'college_phone'         => $details['college_phone'] ?? null,
                    'college_mobile'        => $details['college_mobile'] ?? null,
                    'principal_mobile'      => $details['principal_mobile'] ?? null,
                    'vice_principal_mobile' => $details['vice_principal_mobile'] ?? null,
                    'cleark_mobile'         => $details['cleark_mobile'] ?? null,
                    'address'               => $details['address'] ?? null,
                    'upazilla'              => $details['upazilla'] ?? null,
                    'post_code'             => $details['post_code'] ?? null,
                    'fax'                   => $details['fax'] ?? null,
                    'male_female'           => $details['male_female'] ?? null,
                    'col_type'              => $details['col_type'] ?? null,
                    'college_Location'      => $details['college_Location'] ?? null,
                    'total_Land'            => $details['total_Land'] ?? null,
                    'establish_Year'        => $details['establish_Year'] ?? null,
                    'background'            => $details['background'] ?? null,
                    'website'               => $details['website'] ?? null,
                    'lat'                   => $details['lat'] ?? null,
                    'lng'                   => $details['lng'] ?? null,
                    'logo'                  => $details['logo'] ?? null,
                    'banner'                => $details['banner'] ?? null,
                    'eiin'                  => $details['eiin'] ?? null,
                ]
            );
        }

        // ৩. Courses, Subjects এবং CollegeSubjects আপডেট বা তৈরি করা
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

                // ফাঁকা স্ট্রিং বা ভুল Date ফরম্যাট ঠিক করা
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
