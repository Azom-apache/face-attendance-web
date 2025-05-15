<?php

namespace App\Http\Controllers\Api;

use Log;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function register(Request $request)
    {
        // Validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'face_descriptor' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Validate that face_descriptor contains numeric values
        foreach ($request->face_descriptor as $value) {
            if (!is_numeric($value)) {
                return response()->json(['message' => 'Face descriptor must be an array of numbers'], 422);
            }
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'face_descriptor' => json_encode($request->face_descriptor),
        ]);

        return response()->json(['message' => 'User registered successfully', 'user' => $user], 201);
    }

    public function markAttendance(Request $request)
    {
        // Validate input
        $request->validate([
            'face_descriptor' => 'required|array',
        ]);

        $capturedDescriptor = $request->input('face_descriptor');

        // For demonstration, fetch all users (in production, optimize!)
        $users = User::all();

        foreach ($users as $user) {
            // Decode stored descriptor JSON if stored as string
            $storedDescriptor = is_string($user->face_descriptor) ? json_decode($user->face_descriptor, true) : $user->face_descriptor;

            if ($this->isMatch($capturedDescriptor, $storedDescriptor)) {
                // Mark attendance
                Attendance::create([
                    'user_id' => $user->id,
                    'attended_at' => now(),
                ]);

                return response()->json([
                    'message' => 'Attendance marked for ' . $user->name,
                ]);
            }
        }

        return response()->json([
            'message' => 'No matching face found.',
        ], 404);
    }

    private function isMatch($capturedDescriptor, $storedDescriptor)
    {
        $distance = $this->calculateDistance($capturedDescriptor, $storedDescriptor);
        $similarity = $this->calculateSimilarity($distance);
        return $similarity >= 0.10;  // 10% similarity threshold
    }

    private function calculateDistance($desc1, $desc2)
    {
        $sum = 0;
        for ($i = 0; $i < count($desc1); $i++) {
            $v1 = (float)$desc1[$i];
            $v2 = (float)$desc2[$i];
            $sum += pow($v1 - $v2, 2);
        }
        return sqrt($sum);
    }

    private function calculateSimilarity($distance)
    {
        $maxDistance = 0.6;  // typical max for face-api descriptors
        return max(0, 1 - ($distance / $maxDistance));
    }
    
    
}
