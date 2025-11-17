<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Branch;
use App\Models\Pelanggan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PelangganController extends Controller
{
    // 🟢 Get atau create pelanggan data
    public function show()
    {
        $user = auth()->user();

        // 🔥 AUTO CREATE PELANGGAN JIKA BELUM ADA
        if (!$user->pelanggan) {
            // Buat pelanggan dengan alamat default
            $pelanggan = $this->createDefaultPelanggan($user);
            $user->load('pelanggan'); // Reload relationship
        }

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email
                ],
                'pelanggan' => $user->pelanggan
            ]
        ]);
    }

    // 🟡 Update pelanggan
    public function update(Request $request)
    {
        $user = auth()->user();

        Log::info('Update profile request:', $request->all()); // 👈 TAMBAH LOG

        // 🔥 AUTO CREATE PELANGGAN JIKA BELUM ADA
        if (!$user->pelanggan) {
            $this->createDefaultPelanggan($user);
            $user->load('pelanggan');
        }

        $pelanggan = $user->pelanggan;

        $request->validate([
            'nama' => 'sometimes|string|max:255',
            'alamat' => 'sometimes|string',
            'no_hp' => 'sometimes|string|max:20',
        ]);

        $updateData = [];

        // Update nama jika ada
        if ($request->has('nama')) {
            $user->name = $request->nama;
            $user->save();
            Log::info('Updated user name to: ' . $request->nama); // 👈 LOG
        }

        // 🔍 Kalau alamat diubah → update lat & lon dan branch terdekat
        if ($request->has('alamat') && $request->alamat !== $pelanggan->alamat) {
            Log::info('Updating address from: ' . $pelanggan->alamat . ' to: ' . $request->alamat); // 👈 LOG

            $geo = $this->getCoordinates($request->alamat);

            $updateData['alamat'] = $request->alamat;
            $updateData['latitude'] = $geo['lat'];
            $updateData['longitude'] = $geo['lon'];

            // update branch_id berdasarkan lokasi baru
            $nearestBranch = $this->getNearestBranch($geo['lat'], $geo['lon']);
            $updateData['branch_id'] = $nearestBranch ? $nearestBranch->id : null;

            Log::info('New coordinates: ' . $geo['lat'] . ', ' . $geo['lon']); // 👈 LOG
        }

        if ($request->has('no_hp')) {
            $updateData['no_hp'] = $request->no_hp;
        }

        // Update data pelanggan
        if (!empty($updateData)) {
            $pelanggan->update($updateData);
            Log::info('Updated pelanggan data:', $updateData); // 👈 LOG
        }

        // Reload relationship
        $pelanggan->refresh();
        $user->refresh();

        return response()->json([
            'message' => 'Data pelanggan berhasil diperbarui',
            'data' => [
                'pelanggan' => $pelanggan,
                'user' => [
                    'name' => $user->name,
                    'email' => $user->email
                ]
            ]
        ]);
    }

    // 🔧 Fungsi untuk create pelanggan default
    private function createDefaultPelanggan(User $user)
    {
        $defaultAlamat = "Alamat belum diatur";
        $geo = $this->getCoordinates($defaultAlamat);
        $nearestBranch = $this->getNearestBranch($geo['lat'], $geo['lon']);

        return Pelanggan::create([
            'user_id' => $user->id,
            'alamat' => $defaultAlamat,
            'no_hp' => null,
            'latitude' => $geo['lat'],
            'longitude' => $geo['lon'],
            'branch_id' => $nearestBranch ? $nearestBranch->id : null,
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
                'lat' => (float) $data['lat'],
                'lon' => (float) $data['lon'],
            ];
        }

        // fallback: null
        return ['lat' => null, 'lon' => null];
    }

    // 🔧 Fungsi bantu untuk cari branch terdekat
    private function getNearestBranch($lat, $lon)
    {
        if (!$lat || !$lon) {
            return null;
        }

        $branches = Branch::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

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
