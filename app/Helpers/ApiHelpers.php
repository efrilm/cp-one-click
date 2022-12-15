<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

/**
 * Format response.
 */
class ApiHelpers
{
    /**
     * API Response
     *
     * @var array
     */
    protected static $response = [];

    public function sendNotification($body, $heading, $userId = null)
    {
        $headings = [
            'en' => $heading,
        ];

        $content = [
            'en' => $body,
        ];

        if (!is_null($userId)) {
            $fields = [
                'app_id' => env('ONESIGNAL_APP_ID'),
                'contents' => $content,
                'headings' => $headings,
                'include_external_user_ids' => ["$userId"],
                'channel_for_external_user_ids' => "push",
            ];
        } else {
            $fields = [
                'app_id' => env('ONESIGNAL_APP_ID'),
                'contents' => $content,
                'headings' => $headings,
                'included_segments' => ['All']
            ];
        }


        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . env('ONESIGNAL_API_KEY')
        ])->retry(3, 1000)->post(env('ONESIGNAL_API_URL'), $fields);
    }

    public static function distance($latitude, $longitude, $currentLatitude, $currentLongitude)
    {

        $degrees = rad2deg(
            acos(
                (sin(deg2rad($latitude)) * sin(deg2rad($currentLatitude)))
                    + (cos(deg2rad($latitude)) * cos(deg2rad($currentLatitude)) * cos(deg2rad($longitude - $currentLongitude)))
            )
        );

        $distance = $degrees * 111.13384;

        return (round($distance, 2));
    }
}
