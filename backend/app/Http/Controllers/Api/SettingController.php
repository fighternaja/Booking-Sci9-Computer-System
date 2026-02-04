<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $group = $request->get('group', 'all');
        
        if ($group === 'all') {
            $settings = Setting::all()->groupBy('group');
        } else {
            $settings = Setting::where('group', $group)->get();
        }

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    public function show(string $key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $setting
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string|unique:settings,key',
            'value' => 'required',
            'type' => 'required|in:string,integer,boolean,json,float',
            'group' => 'required|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $setting = Setting::set(
            $request->key,
            $request->value,
            $request->type,
            $request->group,
            $request->description
        );

        return response()->json([
            'success' => true,
            'data' => $setting,
            'message' => 'Setting created successfully'
        ], 201);
    }

    public function update(Request $request, string $key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'value' => 'sometimes|required',
            'type' => 'sometimes|in:string,integer,boolean,json,float',
            'group' => 'sometimes|string',
            'description' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if ($request->has('value')) {
            $value = $request->value;
            if ($request->has('type') && $request->type === 'json') {
                $value = is_array($value) ? json_encode($value) : $value;
            }
            $setting->value = $value;
        }

        if ($request->has('type')) {
            $setting->type = $request->type;
        }

        if ($request->has('group')) {
            $setting->group = $request->group;
        }

        if ($request->has('description')) {
            $setting->description = $request->description;
        }

        $setting->save();

        return response()->json([
            'success' => true,
            'data' => $setting,
            'message' => 'Setting updated successfully'
        ]);
    }

    public function destroy(string $key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found'
            ], 404);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted successfully'
        ]);
    }

    public function getByGroup(string $group)
    {
        $settings = Setting::getByGroup($group);

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }
}

