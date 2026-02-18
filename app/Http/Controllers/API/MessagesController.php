<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Messages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessagesController extends Controller
{
    /**
     * Display a listing of messages (paginated, latest first).
     */
    public function index(Request $request)
    {
        $query = Messages::query();

        // Optional filters
        if ($request->has('status')) {
            $query->withStatus($request->status);
        }

        if ($request->has('search')) {
            $query->search($request->search);
        }

        $messages = $query->latestFirst()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $messages
        ]);
    }

    /**
     * Store a newly created message in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $message = Messages::create([
            'name'    => $request->name,
            'email'   => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'status'  => 'unread',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Message received successfully.',
            'data' => $message
        ], 201);
    }

    /**
     * Display the specified message.
     */
    public function show($id)
    {
        $message = Messages::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $message
        ]);
    }

    /**
     * Update the specified message (e.g. mark as read/unread).
     */
    public function update(Request $request, $id)
    {
        $message = Messages::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'nullable|in:read,unread,archived',
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $message->update($request->only(['name', 'email', 'subject', 'message', 'status']));

        return response()->json([
            'success' => true,
            'message' => 'Message updated successfully.',
            'data' => $message
        ]);
    }

    /**
     * Remove the specified message (soft delete).
     */
    public function destroy($id)
    {
        $message = Messages::find($id);

        if (!$message) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found.'
            ], 404);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.'
        ]);
    }

    /**
     * Show trashed messages (soft-deleted).
     */
    public function trashed()
    {
        $trashed = Messages::onlyTrashed()->latestFirst()->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $trashed
        ]);
    }

    /**
     * Restore a soft-deleted message.
     */
    public function restore($id)
    {
        $restored = Messages::withTrashed()->where('id', $id)->restore();

        if (!$restored) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found or not deleted.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message restored successfully.'
        ]);
    }

    /**
     * Permanently delete a message.
     */
    public function forceDelete($id)
    {
        $deleted = Messages::withTrashed()->where('id', $id)->forceDelete();

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message permanently deleted.'
        ]);
    }
}
