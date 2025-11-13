<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Pelanggan;
use App\Models\Branch;

class PelangganController extends Controller
{
    // 🟢 Tambah pelanggan baru
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'alamat' => 'required|string',
            'no_hp' => 'nullable|string|max:20',
        ]);

        // 🔍 Ambil latitude & longitude dari alamat
        $geo = $this->getCoordinates($request->alamat);

        // ❗ Tentukan branch terdekat
        $nearestBranch = $this->getNearestBranch($geo['lat'], $geo['lon']);

        $pelanggan = Pelanggan::create([
            'user_id' => $request->user_id,
            'alamat' => $request->alamat,
            'no_hp' => $request->no_hp,
            'latitude' => $geo['lat'],
            'longitude' => $geo['lon'],
            'branch_id' => $nearestBranch ? $nearestBranch->id : null,
        ]);

        return response()->json([
            'message' => 'Data pelanggan berhasil disimpan',
            'data' => $pelanggan,
        ]);
    }

    // 🟡 Update pelanggan (alamat berubah → auto update koordinat + branch)
    public function update(Request $request, $id)
    {
        $pelanggan = Pelanggan::findOrFail($id);

        $request->validate([
            'alamat' => 'sometimes|string',
            'no_hp' => 'sometimes|string|max:20',
        ]);

        // 🔍 Kalau alamat diubah → update lat & lon dan branch terdekat
        if ($request->has('alamat')) {
            $geo = $this->getCoordinates($request->alamat);
            $pelanggan->latitude = $geo['lat'];
            $pelanggan->longitude = $geo['lon'];
            $pelanggan->alamat = $request->alamat;

            // update branch_id berdasarkan lokasi baru
            $nearestBranch = $this->getNearestBranch($geo['lat'], $geo['lon']);
            $pelanggan->branch_id = $nearestBranch ? $nearestBranch->id : null;
        }

        if ($request->has('no_hp')) {
            $pelanggan->no_hp = $request->no_hp;
        }

        $pelanggan->save();

        return response()->json([
            'message' => 'Data pelanggan berhasil diperbarui',
            'data' => $pelanggan,
        ]);
    }

    // 🔧 Fungsi bantu untuk ambil koordinat via Nominatim
    private function getCoordinates($alamat)
    {
        $encoded = urlencode($alamat);
        $response = Http::withHeaders([
            'User-Agent' => 'DailyMart/1.0'
        ])->get("https://nominatim.openstreetmap.org/search?q={$encoded}&format=json");

        if ($response->successful() && count($response->json()) > 0) {
            $data = $response->json()[0];
            return [
                'lat' => $data['lat'],
                'lon' => $data['lon'],
            ];
        }

        // fallback: null
        return ['lat' => null, 'lon' => null];
    }

    // 🔧 Fungsi bantu untuk cari branch terdekat
    private function getNearestBranch($lat, $lon)
    {
        $branches = Branch::all();
        $nearestBranch = null;
        $shortestDistance = PHP_INT_MAX;

        foreach ($branches as $branch) {
            $distance = $this->getDistance($lat, $lon, $branch->latitude, $branch->longitude);
            if ($distance < $shortestDistance) {
                $shortestDistance = $distance;
                $nearestBranch = $branch;
            }
        }

        return $nearestBranch;
    }

    // 🔧 Fungsi Haversine untuk hitung jarak (km)
    private function getDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}
