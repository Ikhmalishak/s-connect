<?php

namespace App\Http\Controllers;

use App\Models\EncryptionSetting;
use Illuminate\Http\Request;

class EncryptionSettingController extends Controller
{
    public function index()
    {

        $setting = EncryptionSetting::all();

        return response()->json([
            'data' => $setting,
            'message' => "Successfully fetch database encryption setting"
        ]);
    }

    public function update(Request $request)
    {
        $settings = $request->input('settings', []);

        foreach ($settings as $setting) {
            if (!isset($setting['id'], $setting['is_encrypted'])) {
                continue;
            }

            EncryptionSetting::where('id', $setting['id'])
                ->update(['is_encrypted' => (bool) $setting['is_encrypted']]);
        }

        return response()->json(['message' => 'Encryption settings updated successfully!']);
    }
}

