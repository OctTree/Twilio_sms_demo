<?php

namespace App\Http\Controllers;

use App\UsersPhoneNumber;
use App\Content;
use Illuminate\Http\Request;
use Twilio\Rest\Client;

class HomeController extends Controller
{
    /**
     * Show the forms with users phone number details.
     *
     * @return Response
     */
    public $from_num;

    public function __construct()
    {
        $this->from_num = getenv("TWILIO_NUMBER");
    }
    public function show()
    {
        $users = UsersPhoneNumber::all();
        $current_lists = $this->read($this->from_num);
        return view('welcome')->with("users",$users)->with('current_lists',$current_lists);
    }
    /**
     * Store a new user phone number.
     *
     * @param  Request  $request
     * @return Response
     */
    public function storePhoneNumber(Request $request)
    {
        //run validation on data sent in
        $this->sendMessage('User registration successful!!', $request->phone_number);
        $validatedData = $request->validate([
            'phone_number' => 'required|unique:users_phone_number|numeric',
        ]);
        $user_phone_number_model = new UsersPhoneNumber($request->all());
        $user_phone_number_model->save();
        return back()->with(['success' => "{$request->phone_number} registered"]);
    }
    /**
     * Send message to a selected users
     */
    public function sendCustomMessage(Request $request)
    {
        $validatedData = $request->validate([
            'users' => 'required|array',
            'body' => 'required',
        ]);

        // // iterate over the array of recipients and send a twilio request for each

        $recipients = $validatedData["users"];
        foreach ($recipients as $recipient) {
            $this->sendMessage($validatedData["body"], $recipient);
            $this->storeContent($request);
        }
        return back()->with(['success' => "Messages on their way!"]);
    }
    /**
     * Sends sms to user using Twilio's programmable sms client
     * @param String $message Body of sms
     * @param Number $recipients Number of recipient
     */
    private function sendMessage($message, $recipients)
    {
        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $twilio_number = getenv("TWILIO_NUMBER");
        $client = new Client($account_sid, $auth_token);
        $client->messages->create($recipients, ['from' => $twilio_number, 'body' => $message]);
        
    }
    Public function updateStatus(Request $request)
    {
        \Log::info('RD: ' . json_encode($request->all()));

    }
    Public function storeContent(Request $request)
    {
        $content = new Content();
        $content->from_number = $this->from_num;
        $content->to_number = $request->users[0];
        $content->content = $request->body;
        $content->status = 'D';
        $content->save();
    }
    Public function read($sender)
    {
        $model = new Content();
        $res = $model::where('from_number', '=', $sender)
                       ->orderBy('id', 'asc')
                       ->get();
        return $res;
    }
}
