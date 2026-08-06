<?php

namespace App\Console\Commands;

use App\Models\CollegeUserDetail;
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


    public function handle()
    {
        $this->info("ধাপ ১: ওয়েবসাইট থেকে ফ্রেশ সেশন এবং টোকেন সংগ্রহ করা হচ্ছে...");

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
                $this->error("টোকেন সংগ্রহ করতে ব্যর্থ।");
                return;
            }
        } catch (\Exception $e) {
            $this->error("কানেক্ট করতে সমস্যা: " . $e->getMessage());
            return;
        }

        $colleges = College::whereNotNull('nu_id')->get();
        $total = $colleges->count();

        if ($total == 0) {
            $this->warn("ডাটাবেসে কোনো কলেজ পাওয়া যায়নি!");
            return;
        }

        $this->info("মোট $total টি কলেজের তথ্য লোড করা শুরু হচ্ছে...\n");

        foreach ($colleges as $college) {
            $nuId = $college->nu_id;
            $code = $college->college_code;

            try {
                $detailResponse = Http::withHeaders($this->getHeaders())
                    ->post('https://collegeportal.nu.ac.bd/college-list-view-id', [
                        'id' => $nuId,
                        'collcode' => $code
                    ]);

                if ($detailResponse->successful()) {
                    $detailData = $detailResponse->json();

                    if (!empty($detailData) && isset($detailData['college'])) {
                        $collegeData = $detailData['college'];

                        // ১. মূল colleges টেবিলে শুধু বেসিক ডাটাগুলো আপডেট/সেভ করা
                        $college->update([
                            'college_name'      => $collegeData['college_name'] ?? $college->college_name,
                            'college_name_bn'   => $collegeData['college_name_bn'] ?? $college->college_name_bn,
                            'college_code'      => $collegeData['college_code'] ?? $college->college_code,
                            'email'             => $collegeData['email'] ?? $college->email,
                            'alt_email'         => $collegeData['alt_email'] ?? $college->alt_email,
                            'role'              => $collegeData['role'] ?? $college->role,
                            'approved'          => $collegeData['approved'] ?? $college->approved,
                            'otp'               => $collegeData['otp'] ?? $college->otp,
                            'password'          => $collegeData['password'] ?? $college->password,
                            'email_verified_at' => $collegeData['email_verified_at'] ?? $college->email_verified_at,
                            'remember_token'    => $collegeData['remember_token'] ?? $college->remember_token,
                        ]);

                        // ২. বাকी সব ডিটেইলস college_user_details টেবিলে সেভ করা
                        $this->saveCollegeUserDetails($college, $collegeData);

                        // ৩. সাবজেক্ট ও কোর্সগুলো সেভ করা
                        if (isset($detailData['collegeSubjectLists']) && count($detailData['collegeSubjectLists']) > 0) {
                            $this->saveSubjectsData($college, $detailData['collegeSubjectLists']);
                        }

                        $this->info("College Code: {$code} এর সকল ডাটা সফলভাবে প্রসেস হয়েছে।");
                    }
                }
            } catch (\Exception $e) {
                $this->error("College Code: {$code} এ এরর: " . $e->getMessage());
            }

            sleep(1);
        }

        $this->info("সকল তথ্য সফলভাবে সেভ করা সম্পন্ন হয়েছে!");
    }

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
     * college_user_details টেবিলে ডাটা সেভ করার লজিক
     */
    private function saveCollegeUserDetails(College $college, array $collegeData): void
    {
        $details = $collegeData['college_user_detail'] ?? [];

        if (!empty($details)) {
            CollegeUserDetail::updateOrCreate(
                ['college_id' => $college->id],
                [
                    'user_id'               => $details['user_id'] ?? null,
                    // 'college_code'       => $details['college_code'] ?? null, <--- এই লাইনটি মুছে দিন বা কমেন্ট করে রাখুন
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
    }

    /**
     * Course, Subject এবং CollegeSubject টেবিলে ডাটা সেভ করার লজিক
     */
    private function saveSubjectsData(College $college, array $subjectLists): void
    {
        foreach ($subjectLists as $item) {
            $course = Course::firstOrCreate(
                ['course_code' => $item['course_code']],
                ['course_name' => $item['course_name'] ?? null]
            );

            $subject = Subject::firstOrCreate(
                ['subject_code' => $item['subject_code']],
                ['subject_name' => $item['subject_name'] ?? null]
            );

            $seatCapacity = (isset($item['seat_capacity']) && trim($item['seat_capacity']) !== '')
                ? $item['seat_capacity']
                : null;

            $affDate = (isset($item['aff_date']) && !in_array($item['aff_date'], ['0000-00-00', '']))
                ? $item['aff_date']
                : null;

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
