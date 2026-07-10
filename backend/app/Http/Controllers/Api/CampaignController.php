<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DonationCampaign;


class CampaignController extends Controller
{
    public function index()
    {
        $campaigns = DonationCampaign::query()
            ->whereNotIn('status', ['pending', 'rejected'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $campaigns
        ]);
    }
}
