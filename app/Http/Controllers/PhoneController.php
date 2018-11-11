<?php

namespace App\Http\Controllers;

use App\FonoApi;
use App\Phone;
use Illuminate\Http\Request;

class PhoneController extends Controller
{
    public function search(Request $request, FonoApi $fonoApi)
    {
        $name = strtolower($request->input('name'));
        if (strlen($name) < 3) {
            return response(['error' => 'please input more than 3 chars'], 400);
        }

        try {
            $data = $fonoApi->getDevice($name);
            $dbLikeData = $this->cacheFonoData($data);
            return $this->filterStartsWith($dbLikeData, $name);
        } catch (\Exception $e) {
            return $this->cachedData($name);
        }
    }

    private function cacheFonoData($data)
    {
        $result = collect();

        foreach ($data as $phone) {
            $dbPhone = Phone::whereName(strtolower($phone->DeviceName))->first();
            if ($dbPhone !== null) {
                $result->push($dbPhone);
            } else {
                $phoneData = array_merge((array) $phone, $this->getStoredImages($phone->DeviceName));
                $phoneData['previewImage'] = $this->getPreviewLink($phone->DeviceName);
                $result->push(Phone::create([
                    'name' => strtolower($phone->DeviceName),
                    'fonodata' => json_encode($phoneData)
                ]));

            }

        }
        return $result;
    }

    private function filterStartsWith($dbLikeData, $searchWord)
    {
        return $dbLikeData->filter(function ($phone) use ($searchWord) {
            return preg_match("/(^|\s)$searchWord/", $phone->name);
        })->map(function ($phone) {
            return json_decode($phone->fonodata);
        });
    }

    private function cachedData($name)
    {
        return $this->filterStartsWith(Phone::where('name', 'like', "%$name%")->get(), $name);
    }

    private function getStoredImages($deviceName)
    {
        $deviceName = preg_replace('/\\s+/', '', strtolower($deviceName));


        $backPath = $deviceName . '_back.png';
        $backUrl = storage_path('app/public/' . $backPath);
        $frontPath = $deviceName . '_front.png';
        $frontUrl = storage_path('app/public/' . $frontPath);
        $sidePath = $deviceName . '_side.png';
        $sideUrl = storage_path('app/public/' . $sidePath);
        if (!file_exists($backUrl) || !file_exists($frontUrl) || !file_exists($sideUrl)) {
            return [];
        }
        return [
            'frontImage' => asset('storage/' . $frontPath),
            'backImage' => asset('storage/' . $backPath),
            'sideImage' => asset('storage/' . $sidePath)
        ];
    }

    private function getPreviewLink($DeviceName)
    {
        $url = "https://www.emag.ro/search/" . urlencode($DeviceName);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        if (preg_match('/js-product-data.*?img src="(.*?)"/sm', $result, $links)){
            return $links[1];
        }
        return "https://www.gsmarena.com/lg_nexus_5-pictures-5705.php";
    }


}
