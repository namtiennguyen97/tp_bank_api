<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TPBankController extends Controller
{
    public function getToken(Request $request)
    {
        $username = $request->username ?? '';
        $password = $request->password ?? '';
        $url = "https://ebank.tpb.vn/gateway/api/auth/login";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "DEVICE_ID: LYjkjqGZ3HhGP5520GxPP2j94RDMC7Xje77MI7" . rand(10000000, 999999999999),
            "PLATFORM_VERSION: 91",
            "DEVICE_NAME: Chrome",
            "SOURCE_APP: HYDRO",
            "Authorization: Bearer",
            "Content-Type: application/json",
            "Accept: application/json, text/plain, */*",
            "Referer: https://ebank.tpb.vn/retail/vX/login?returnUrl=%2Fmain",
            "sec-ch-ua-mobile: ?0",
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36",
            "PLATFORM_NAME: WEB",
            "APP_VERSION: 1.3",
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = '{"username":"' . $username . '","password":"' . $password . '"}';

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);


        $resp = json_decode($resp);

        if(!isset($resp->access_token)){
            return response()->json([
                'code' => Response::HTTP_BAD_GATEWAY,
                'msg' => 'Tài khoản hoặc mật khẩu không đúng!',
                'devMsg' => 'Nếu đang đọc response này, thì có nghĩa đang nhập sai pass hay username thực, hoặc có lẽ do bật bảo mật 2 lớp chăng? Yên tâm vì đây không phải trang web chiếm đoạt tài khoản. Ngồi rảnh thì làm 1 trang để học react thôi.'
            ]);
        }

        return \response()->json([
            'code' => Response::HTTP_OK,
            'msg' => 'Đăng nhập thành công!',
            'data' => $resp
        ]);
    }

    public function getHistory()
    {
        $ngay_bat_dau_check = '20230127';
        $ngay_ket_thuc_check = '20230303';


//        if(session()->has('tp_account')){
//           $accountData = session()->get('tp_account');
//        } else{
//           $accountData = [];
//        }
//        if(empty($accountData)){
//            return response()->json([
//               'msg' => 'Get token first'
//            ]);
//        }
        $user = DB::table('users')->first();


        $token = $user->tp_token;
        $stk_tpbank = $user->full_username;


        $url = "https://ebank.tpb.vn/gateway/api/smart-search-presentation-service/v1/account-transactions/find";
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Connection: keep-alive",
            "DEVICE_ID: LYjkjqGZ3HhGP5520GxPP2j94RDMC7Xje77MI75RYBVR",
            "PLATFORM_VERSION: 91",
            "DEVICE_NAME: Chrome",
            "SOURCE_APP: HYDRO",
            "Authorization: Bearer " . $token,
            "XSRF-TOKEN=3229191c-b7ce-4772-ab93-55a",
            "Content-Type: application/json",
            "Accept: application/json, text/plain, */*",
            "sec-ch-ua-mobile: ?0",
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36",
            "PLATFORM_NAME: WEB",
            "APP_VERSION: 1.3",
            "Origin: https://ebank.tpb.vn",
            "Sec-Fetch-Site: same-origin",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Dest: empty",
            "Referer: https://ebank.tpb.vn/retail/vX/main/inquiry/account/transaction?id=" . $stk_tpbank,
            "Accept-Language: vi-VN,vi;q=0.9,fr-FR;q=0.8,fr;q=0.7,en-US;q=0.6,en;q=0.5",
            "Cookie: _ga=GA1.2.1679888794.1623516; _gid=GA1.2.580582711.16277; _gcl_au=1.1.756417552.1626666; Authorization=" . $token,
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = '{"accountNo":"' . $stk_tpbank . '","currency":"VND","fromDate":"' . $ngay_bat_dau_check . '","toDate":"' . $ngay_ket_thuc_check . '","keyword":""}';

        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        //for debug only!
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $resp = curl_exec($curl);
        curl_close($curl);
        return json_decode($resp);
    }

    public function getAccountInfo(Request $request){
        $token = $request->token ?? '';
        if(!$token){
            return \response()->json([
                'code' => Response::HTTP_BAD_GATEWAY,
                'msg' => 'Test cái gì, truyền thiếu token',
                'data' => []
            ]);
        }
        $url = "https://ebank.tpb.vn/gateway/api/common-presentation-service/v1/bank-accounts?function=home";

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, false); //important
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Accept: application/json, text/plain, */*",
            "Accept-Encoding: gzip, deflate, br",
            "Accept-Language: vi-VN,vi;q=0.9,fr-FR;q=0.8,fr;q=0.7,en-US;q=0.6,en;q=0.5",
            "APP_VERSION: 1.3",
            "Authorization: Bearer " . $token,
            "Connection: keep-alive",
            "Content-Type: application/json",
            "Cookie: _ga=GA1.2.1679888794.1623516; _gid=GA1.2.580582711.16277; _gcl_au=1.1.756417552.1626666; Authorization=" . $token,
            "DEVICE_ID: LYjkjqGZ3HhGP5520GxPP2j94RDMC7Xje77MI75RYBVR",
            "DEVICE_NAME: Chrome",
            "Host: ebank.tpb.vn",
            "PLATFORM_NAME: WEB",
            "PLATFORM_VERSION: 91",
            "Referer: https://ebank.tpb.vn/retail/vX/main",
            "sec-ch-ua: Chromium;v=110, Not A(Brand;v=24, Microsoft Edge;v=110", // ?
            "sec-ch-ua-mobile: ?0",
            "sec-ch-ua-platform: Windows",
            "Sec-Fetch-Dest: empty",
            "Sec-Fetch-Mode: cors",
            "Sec-Fetch-Site: same-origin",
            "SOURCE_APP: HYDRO",
            "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36",
        );

//        $header = array(
//            "Accept: application/json, text/plain, */*",
//"Accept-Encoding: gzip, deflate, br",
//"Accept-Language: en-US,en;q=0.9",
//"APP_VERSION: 2023.02.10",
//"Authorization: Bearer eyJraWQiOiJNYmV1VmVVWlhVT2FJcDgwYmx1XC9sanFOQjNKZE9aSDgxQ3JGU0tpMmVcL2M9IiwiY3R5IjoiSldUIiwiZW5jIjoiQTEyOENCQy1IUzI1NiIsImFsZyI6ImRpciJ9..iIClIcoAUtlQMMtTeBhaLA.zijFx7T_54VYzIWYuRqbbSwET1b4fM2zyDygPczLTYmPzSnUIbKE6oDYropgFiZjW6UzWHfPrT8GTuWHIucDPeJcp7_2mRDis3wYhyqR-v51mRT75fYSxbNaID_qPsXOeUaCsLriBDUJb7LW_s1kgu2NRdAasye-6GohKFN4MaS8Cfl-DZpHOdAQ2FfpJ6WsB9Z3kG5kBm0TKYJtgNzJjFlL_MYifaT-ElwSNa12Ea-y7_iJoIeReMAWwg9rIKUgWkY4w13wosVWVGPi9UktB6EJW2HcTzxV8FBE-DTM7Dqail3vYpB3ud77ofURI8K1VJe3RdjLljiq0PqQBA67oytz5ZrPFPQfQZrdJP9TgqqGhvHb29YTfc4lrF3uh-4gByM4qOlDjNzfrRxmBN0xmG42I3TCyeGyLMF9CPFLHYPrBVytZAoPWD2jAwY41MqT3O_x-6WFKCT2CQbJazaOObtqv8E8ugpUHhw86-WZG3cbrUGbXXeJnUxDlpl37Xxv.EEuLgjEfB7SxlaYo_MsYEg",
//"Connection: keep-alive",
//"Content-Type: application/json",
//"Cookie: Authorization=eyJraWQiOiJNYmV1VmVVWlhVT2FJcDgwYmx1XC9sanFOQjNKZE9aSDgxQ3JGU0tpMmVcL2M9IiwiY3R5IjoiSldUIiwiZW5jIjoiQTEyOENCQy1IUzI1NiIsImFsZyI6ImRpciJ9..iIClIcoAUtlQMMtTeBhaLA.zijFx7T_54VYzIWYuRqbbSwET1b4fM2zyDygPczLTYmPzSnUIbKE6oDYropgFiZjW6UzWHfPrT8GTuWHIucDPeJcp7_2mRDis3wYhyqR-v51mRT75fYSxbNaID_qPsXOeUaCsLriBDUJb7LW_s1kgu2NRdAasye-6GohKFN4MaS8Cfl-DZpHOdAQ2FfpJ6WsB9Z3kG5kBm0TKYJtgNzJjFlL_MYifaT-ElwSNa12Ea-y7_iJoIeReMAWwg9rIKUgWkY4w13wosVWVGPi9UktB6EJW2HcTzxV8FBE-DTM7Dqail3vYpB3ud77ofURI8K1VJe3RdjLljiq0PqQBA67oytz5ZrPFPQfQZrdJP9TgqqGhvHb29YTfc4lrF3uh-4gByM4qOlDjNzfrRxmBN0xmG42I3TCyeGyLMF9CPFLHYPrBVytZAoPWD2jAwY41MqT3O_x-6WFKCT2CQbJazaOObtqv8E8ugpUHhw86-WZG3cbrUGbXXeJnUxDlpl37Xxv.EEuLgjEfB7SxlaYo_MsYEg; NSC_fcbol_hbufxbz_bqj_mcwt_iuuq=ffffffff09da085945525d5f4f58455e445a4a4229aa; XSRF-TOKEN=fcc9fa65-e2a1-4fd0-9918-9562f8ebe7f4",
//"DEVICE_ID: 21nvNmIrymf2qFLMwcvo7nzGoT96cljyp9HHF7UUzmkMw",
//"DEVICE_NAME: Chrome",
//"Host: ebank.tpb.vn",
//"PLATFORM_NAME: WEB",
//'PLATFORM_VERSION: 110',
//"Referer: https://ebank.tpb.vn/retail/vX/main",
//"sec-ch-ua: Chromium;v=110, Not A(Brand;v=24, Microsoft Edge;v=110",
//"sec-ch-ua-mobile: ?0",
//"sec-ch-ua-platform: Windows",
//"Sec-Fetch-Dest: empty",
//"Sec-Fetch-Mode: cors",
//"Sec-Fetch-Site: same-origin",
//"SOURCE_APP: HYDRO",
//"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36 Edg/110.0.1587.57"
//        );

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $resp = curl_exec($curl);
        curl_close($curl);


        return \response()->json([
            'code' => Response::HTTP_OK,
            'msg' => 'Thành công',
            'data' => json_decode($resp, true)
        ]);
    }
}
