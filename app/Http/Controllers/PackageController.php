<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Package;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    public function index()
    {
        $packages = Package::orderBy('channel')->orderBy('min_units')->get();
        return view('packages.index', compact('packages'));
    }

    public function store(Request $request)
    {
        $data = $this->validatePackage($request);
        Package::create($data);

        return redirect()->route('packages.index')->with('success', "Package \"{$data['name']}\" created.");
    }

    public function update(Request $request, Package $package)
    {
        $data = $this->validatePackage($request);
        $package->update($data);

        return redirect()->route('packages.index')->with('success', 'Package updated.');
    }

    public function destroy(Package $package)
    {
        $package->delete();
        return redirect()->route('packages.index')->with('success', 'Package deleted — campaigns that used it keep their rates.');
    }

    /**
     * One-click starter tiers so the structure is obvious: rates step down
     * from the account default as volume grows. All fully editable after.
     */
    public function seedDefaults()
    {
        if (Package::where('channel', 'sms')->exists()) {
            return redirect()->route('packages.index')->with('error', 'SMS packages already exist — edit those instead.');
        }

        $base = (float) AppSetting::get('default_client_rate', '0.25');
        $tiers = [
            ['name' => 'Starter',  'min_units' => 500,   'multiplier' => 1.00],
            ['name' => 'Growth',   'min_units' => 1000,  'multiplier' => 0.95],
            ['name' => 'Business', 'min_units' => 5000,  'multiplier' => 0.90],
            ['name' => 'Volume',   'min_units' => 10000, 'multiplier' => 0.85],
        ];

        foreach ($tiers as $tier) {
            Package::create([
                'name'        => $tier['name'],
                'channel'     => 'sms',
                'min_units'   => $tier['min_units'],
                'unit_price'  => round($base * $tier['multiplier'], 4),
                'description' => number_format($tier['min_units']) . '+ SMS per campaign',
                'active'      => true,
            ]);
        }

        return redirect()->route('packages.index')->with('success', 'Starter SMS tiers created — adjust the rates to your pricing.');
    }

    private function validatePackage(Request $request): array
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'channel'     => 'required|in:sms,email',
            'min_units'   => 'required|integer|min:1',
            'unit_price'  => 'required|numeric|min:0',
            'description' => 'nullable|string|max:191',
            'active'      => 'boolean',
        ]);
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
