<?php

namespace App\Helpers;

class Helpers
{
    public static function formatPhoneNumber($phone): ?string
    {
        /**
         * Remove all spaces and hyphens from the phone number
         * and format it to fit the +2547xxxxxxxx, like +254712345678
         * return false if the phone number is invalid (to help in returning validation errors)
         */
        $phone = preg_replace('/\s|-/', '', (string) $phone);
        if (str_starts_with($phone, '+254') && strlen($phone) === 13) {
            return $phone;
        }
        if (str_starts_with($phone, '254') && strlen($phone) === 12) {
            return '+'.$phone;
        }
        if (str_starts_with($phone, '254') && strlen($phone) === 13 && $phone[3] === '0') {
            return '+254'.substr($phone, 4);
        }
        if ($phone[0] === '0' && strlen($phone) === 10 && in_array($phone[1], ['1', '2', '7'])) {
            return '+254'.substr($phone, 1);
        }

        if (in_array($phone[0], ['1', '2', '7']) && strlen($phone) === 9) {
            return '+254'.$phone;
        }

        return null;
    }
}
