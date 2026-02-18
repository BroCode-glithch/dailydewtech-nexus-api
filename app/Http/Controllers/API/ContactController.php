<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Messages;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactReceived;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
        ]);

        $message = Messages::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => 'unread',
        ]);

        // Send notification to site admin
        try {
            Mail::to(config('mail.from.address'))->send(new ContactReceived($message));
        } catch (\Exception $e) {
            // don't break UX if mail fails; log and continue
            logger()->error('Contact mail failed: ' . $e->getMessage());
        }

        return response()->json(['message' => 'Message received. Thank you.'], 201);
    }
}
