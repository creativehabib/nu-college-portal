<?php

namespace App\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\College;
use App\Models\Course;
use App\Models\Subject;
use App\Models\CollegeSubject;

class FetchCollegeSubjects extends Command
{
    protected $signature = 'app:fetch-college-subjects';
    protected $description = 'Fetch and save courses, subjects, and college_subjects for existing colleges from NU API';

    private string $cookie = 'XSRF-TOKEN=eyJpdiI6InNMaHdmNUdTT1NGU09iL3pxY1JyMWc9PSIsInZhbHVlIjoibWdDUVFiNnRDL0tHZ2VpaXhZV2cyOU53eE9VWVFPYlFWRVdBU1JoWkFKMWVGclRrdjVRc29WdmpJOHVCNHdaNzJNdk1pOXBseVNLL095eEx2aFBKV1V0dDV1LzRqSjZFb2hSRXNLSktTNkpUaGRMdEJBKzF4U1gyUytnbHV1SG4iLCJtYWMiOiIyYjJkM2NjZjE3MjBjMjkzZWEwNDljM2IzYjA4OWQyYmViMzVkNzJkZGQ5N2FhNzcxMzA4ZDdjNmY0NTE0NjAzIiwidGFnIjoiIn0%3D; expires=Wed, 05 Aug 2026 16:05:12 GMT; Max-Age=7200; path=/; secure; samesite=lax';
    private string $xsrfToken = 'eyJpdiI6IkVtcFM5WkpBb01mSjNjUDdkYnYvM1E9PSIsInZhbHVlIjoiWU1CR3NOVlB0ZkpkV3c3eFNmVWQzTExQcUQ4YlJna1d4MmxCaDN4VmpnOWk2N1B1bW02ZnNWMTNGK0kwd3hHcWlKN1ZyT3VUK3l0U2VSVjVLQVBrVlBsM29FVy9NWEpGR0lDVTlZSlk5c3BSK293MEJod1RUbWE5UlBKWjZsM3IiLCJtYWMiOiI5NWVhMTc4ZjU2ZDliNjNkZWVhZjA3ZmYzNjhlMzI1ODgwMDFmMjNhOWMyMzJhODExYjZmYTE2NDQ1MTJhM2U0IiwidGFnIjoiIn0=';

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
                $this->error("টোকেন সংগ্রহ করতে ব্যর্থ। সার্ভার হয়তো ব্লক করেছে।");
                return;
            }
        } catch (\Exception $e) {
            $this->error("ওয়েবসাইটে কানেক্ট করতে সমস্যা হচ্ছে: " . $e->getMessage());
            return;
        }

        // ২. ডাটাবেস থেকে সেভ করা কলেজগুলো বের করা
        $colleges = College::whereNotNull('nu_id')->get();
        $total = $colleges->count();

        if ($total == 0) {
            $this->warn("ডাটাবেসে কোনো কলেজ পাওয়া যায়নি!");
            return;
        }

        $this->info("মোট $total টি কলেজের সাবজেক্ট ও কোর্স লোড করা শুরু হচ্ছে...\n");

        // ৩. লুপ চালিয়ে প্রতিটি কলেজের আইডি অনুযায়ী সাবজেক্ট ডাটা আনা
        foreach ($colleges as $college) {
            $nuId = $college->nu_id;
            $code = $college->college_code;

            try {
                // JSON ফরম্যাটে ডাটা পাঠানো হচ্ছে ('college_code' এর বদলে 'collcode' ব্যবহার করে)
                $detailResponse = Http::withHeaders($this->getHeaders())
                    ->post('https://collegeportal.nu.ac.bd/college-list-view-id', [
                        'id' => $nuId,
                        'collcode' => $code
                    ]);

                if ($detailResponse->successful()) {
                    $detailData = $detailResponse->json();

                    if(empty($detailData)) {
                        $this->warn("College Code: {$code} এর জন্য API ফাঁকা রেসপন্স দিয়েছে!");
                    } else {
                        if (!isset($detailData['collegeSubjectLists']) || count($detailData['collegeSubjectLists']) == 0) {
                            $this->error("College Code: {$code} এর ডাটাতে কোনো সাবজেক্ট পাওয়া যায়নি!");
                        } else {
                            $subjectCount = count($detailData['collegeSubjectLists']);
                            $this->info("College Code: {$code} এর জন্য {$subjectCount} টি সাবজেক্ট পাওয়া গেছে। সেভ করা হচ্ছে...");

                            $this->saveSubjectsData($college, $detailData['collegeSubjectLists']);
                        }
                    }
                } else {
                    $this->error("College Code: {$code} এর API হিট ফেইল করেছে। Status: " . $detailResponse->status());
                }
            } catch (\Exception $e) {
                $this->error("College Code: {$code} এর ডাটা সেভ করতে এরর: " . $e->getMessage());
                Log::error("Error fetching data for college ID $nuId : " . $e->getMessage());
            }

            // সার্ভারে ব্লক হওয়া এড়াতে প্রতি রিকোয়েস্টের পর ১ সেকেন্ড বিরতি
            sleep(1);
        }

        $this->newLine();
        $this->info("প্রক্রিয়াটি সফলভাবে সম্পন্ন হয়েছে!");
    }

    /**
     * API রিকোয়েস্টের জন্য প্রয়োজনীয় হেডার্স
     */
    private function getHeaders(): array
    {
        return [
            'Content-Type'     => 'application/json',
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
     * ডাটাবেস আপডেট করার লজিক (Course, Subject এবং CollegeSubject এর জন্য)
     */
    private function saveSubjectsData(College $college, array $subjectLists): void
    {
        foreach ($subjectLists as $item) {

            // ১. Course টেবিলে কোর্স তৈরি বা খুঁজে বের করা
            $course = Course::firstOrCreate(
                ['course_code' => $item['course_code']],
                ['course_name' => $item['course_name'] ?? null]
            );

            // ২. Subject টেবিলে সাবজেক্ট তৈরি বা খুঁজে বের করা
            $subject = Subject::firstOrCreate(
                ['subject_code' => $item['subject_code']],
                ['subject_name' => $item['subject_name'] ?? null]
            );

            // ৩. ডাটা ক্লিনিং (ফাঁকা স্ট্রিং আসলে null করে দেওয়া)
            $seatCapacity = (isset($item['seat_capacity']) && trim($item['seat_capacity']) !== '')
                ? $item['seat_capacity']
                : null;

            $affDate = (isset($item['aff_date']) && !in_array($item['aff_date'], ['0000-00-00', '']))
                ? $item['aff_date']
                : null;

            // ৪. Pivot/রিলেশনশিপ টেবিলে ডাটা সেভ করা
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
