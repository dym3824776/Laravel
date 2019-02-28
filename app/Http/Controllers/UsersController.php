<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class UsersController extends Controller
{

    public function __construct()
    {
	    $this->middleware('auth', [
		    'except' => ['show', 'create', 'store', 'index','confirmEmail']
	    ]);

        $this->middleware('auth',[
            'only' => ['edit', 'update', 'destroy']
        ]);

        $this->middleware('guest',[
            'only' => ['create']
        ]);
    }

    public function index()
    {
        $users = User::paginate(30);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }


	public function show(User $user)
	{
		$statuses = $user->statuses()
			->orderBy('created_at', 'desc')
			->paginate(10);
		return view('users.show', compact('user', 'statuses'));
	}


    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        return view('users.edit', compact('user'));
    }


    public function update($id, Request $request)
    {
        $this->validate($request, [
            'name' => 'required|max:50',
            'password' => 'confirmed|min:6'
        ]);

        $user = User::findOrFail($id);
        $this->authorize('update', $user);

        $data = [];
        $data['name'] = $request->name;
        if($request->password) {
            $data['password'] = bcrypt($request->password);
        }
        $user->update($data);

        session()->flash('success', '个人资料更新成功!');

        return redirect()->route('users.show', $id);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $this->authorize('destroy', $user);
        $user->delete();
        session()->flash('success', '成功删除用户！');
        return back();
    }

    public function store(Request $request)
    {
        $this->validate($request,[
            'name' => 'required|max:50',
            'email' => 'required|email|unique:users|max:255',
            'password' => 'required|confirmed|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $this->sendEmailConfirmationTo($user);
        session()->flash('success', '验证邮件已发送到你的注册邮箱上，请注意查收。');
        return redirect()->route('users.show', [$user]);
    }

    protected function sendEmailConfirmationTo($user)
    {
    	$view = 'emails.confirm';
    	$data = compact('user');
    	$from = "dym0308@163.com";
    	$name = "Yuming";
    	$to   = $user->email;
    	$subject = '感谢注册 Sample！请确认你的邮箱。';

    	Mail::send($view, $data, function($message) use ($from, $name, $to, $subject){
    		$message->from($from, $name)->to($to)->subject($subject);
	    });
    }

	public function confirmEmail($token)
	{
		$user = User::where('activation_token', $token)->firstOrFail();

		$user->activated = true;
		$user->activation_token = null;
		$user->save();

		Auth::login($user);
		session()->flash('success', '恭喜你，激活成功！');
		return redirect()->route('users.show', [$user]);
	}

	public function followings(User $user)
	{
		$users = $user->followings()->paginate(30);
		$title = $user->name . '关注的人';
		return view('users.show_follow', compact('users', 'title'));
	}

	public function followers(User $user)
	{
		$user = $user->followers()->paginate(30);
		$title = $user->name . '的粉丝';
		return view('users.show_follow',compact('user','title'));
	}
}
