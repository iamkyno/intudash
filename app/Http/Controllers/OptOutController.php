<?php

namespace App\Http\Controllers;

use App\Models\OptOut;
use App\Models\SmsReply;
use App\Services\OptOutService;
use App\Services\PhoneNormalizer;
use Illuminate\Http\Request;

class OptOutController extends Controller
{
    public function index()
    {
        $optOuts = OptOut::latest('opted_out_at')->paginate(30);
        $recentReplies = SmsReply::latest()->limit(50)->get();
        $replyCount = SmsReply::count();

        return view('opt_outs.index', compact('optOuts', 'recentReplies', 'replyCount'));
    }

    public function store(Request $request, OptOutService $service)
    {
        $data = $request->validate(['phone' => 'required|string|max:20']);

        $normalized = PhoneNormalizer::normalize($data['phone']);
        if (!$normalized) {
            return back()->with('error', 'That phone number is not a valid South African mobile number.');
        }

        $service->optOut($normalized, 'manual');

        return back()->with('success', "{$normalized} added to the do-not-contact list.");
    }

    public function destroy(OptOut $optOut)
    {
        $number = $optOut->phone_normalized;
        $optOut->delete();

        return back()->with('success', "{$number} removed from the do-not-contact list. They can be messaged again.");
    }
}
