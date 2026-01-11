<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['user', 'room', 'booking']);

        if ($request->has('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // แสดงเฉพาะรีวิวที่ visible
        if (!$request->has('include_hidden')) {
            $query->where('is_visible', true);
        }

        $reviews = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $reviews
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'room_id' => 'required|exists:rooms,id',
            'booking_id' => 'nullable|exists:bookings,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000'
        ]);

        // ตรวจสอบว่าผู้ใช้สามารถรีวิวได้หรือไม่
        if ($request->booking_id) {
            $booking = Booking::findOrFail($request->booking_id);
            
            if ($booking->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // ตรวจสอบว่าการจองผ่านไปแล้ว
            if ($booking->end_time > now()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถรีวิวได้จนกว่าการจองจะสิ้นสุด'
                ], 400);
            }

            // ตรวจสอบว่ามีรีวิวแล้วหรือยัง
            $existingReview = Review::where('user_id', Auth::id())
                ->where('booking_id', $request->booking_id)
                ->first();

            if ($existingReview) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณได้รีวิวการจองนี้แล้ว'
                ], 400);
            }
        } else {
            // ถ้าไม่ระบุ booking_id ต้องตรวจสอบว่ามีการจองที่ผ่านไปแล้ว
            $hasBooking = Booking::where('user_id', Auth::id())
                ->where('room_id', $request->room_id)
                ->where('status', 'approved')
                ->where('end_time', '<', now())
                ->exists();

            if (!$hasBooking) {
                return response()->json([
                    'success' => false,
                    'message' => 'คุณต้องมีการจองที่ผ่านไปแล้วจึงจะสามารถรีวิวได้'
                ], 400);
            }
        }

        $review = Review::create([
            'user_id' => Auth::id(),
            'room_id' => $request->room_id,
            'booking_id' => $request->booking_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'is_visible' => true
        ]);

        return response()->json([
            'success' => true,
            'data' => $review->load(['user', 'room', 'booking']),
            'message' => 'เพิ่มรีวิวสำเร็จ'
        ], 201);
    }

    public function show(Review $review): JsonResponse
    {
        if (!$review->is_visible && $review->user_id !== Auth::id()) {
            $user = Auth::user();
            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => $review->load(['user', 'room', 'booking'])
        ]);
    }

    public function update(Request $request, Review $review): JsonResponse
    {
        $user = Auth::user();
        
        // เจ้าของรีวิวหรือแอดมินเท่านั้นที่แก้ไขได้
        if ($review->user_id !== Auth::id() && (!$user || $user->role !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'is_visible' => 'sometimes|boolean'
        ]);

        $review->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $review->load(['user', 'room', 'booking']),
            'message' => 'อัปเดตรีวิวสำเร็จ'
        ]);
    }

    public function destroy(Review $review): JsonResponse
    {
        $user = Auth::user();
        
        // เจ้าของรีวิวหรือแอดมินเท่านั้นที่ลบได้
        if ($review->user_id !== Auth::id() && (!$user || $user->role !== 'admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบรีวิวสำเร็จ'
        ]);
    }

    /**
     * ดึงข้อมูลรีวิวและคะแนนเฉลี่ยของห้อง
     */
    public function getRoomReviews(Room $room): JsonResponse
    {
        $reviews = Review::where('room_id', $room->id)
            ->where('is_visible', true)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();

        $averageRating = Review::getAverageRating($room->id);
        $reviewCount = Review::getReviewCount($room->id);

        // คำนวณการกระจายคะแนน
        $ratingDistribution = [];
        for ($i = 1; $i <= 5; $i++) {
            $ratingDistribution[$i] = Review::where('room_id', $room->id)
                ->where('is_visible', true)
                ->where('rating', $i)
                ->count();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'average_rating' => round($averageRating, 2),
                'review_count' => $reviewCount,
                'rating_distribution' => $ratingDistribution
            ]
        ]);
    }
}

