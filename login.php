<?php

class EditorLogin
{
	var $_pwfile;

	function EditorLogin()
	{
		$this->_pwfile = "/home/davense/aggregator/pw";
	}

	function _Authenticate($user = "", $pass = "")
	{
		$authenticated = false;
		$pass_hash = sha1($pass."§22");

		if($user == "" and isset($_COOKIE["bwlogin"]))
		{
			list($user, $pass_hash) = explode("|", $_COOKIE["bwlogin"]);
			$authenticated = $this->_CheckPassword($user, $pass_hash);
		}
		elseif($user != "")
		{
			$authenticated = $this->_CheckPassword($user, $pass_hash);
			if($authenticated)
				setcookie("bwlogin", "$user|$pass_hash", time() + 3600 * 24 * 365, "/blogwalk/");
		}

		return $authenticated;
	}

	function _CheckPassword($username, $password_hash)
	{
		$data = file($this->_pwfile);

		foreach($data as $line)
		{
			list($user, $pass) = explode(":", trim($line));

			if($user == $username)
				return ($pass == $password_hash) ? true : false;
		}

		return false;
	}

	function GetUsername()
	{
		$user = "";
		if(isset($_COOKIE["bwlogin"]))
			list($user, $_) = explode("|", $_COOKIE["bwlogin"]);

		return $user;
	}

	# byter lösenord på inloggad person
	function ChangePassword($password)
	{
		global $salt;

		$username = $this->GetUsername();
		$olddata = file($this->_pwfile);
		$newdata = "";

		foreach($olddata as $line)
		{
			list($user, $pass) = explode(":", trim($line));

			if($user == $username)
				$newdata .= "$username:".sha1($password.$salt)."\n";
			else
				$newdata .= $line;
		}

		$handle = fopen($this->_pwfile, "w+");
		fwrite($handle, $newdata);
		fclose($handle);
	}

	function Logout()
	{
		unset($_COOKIE["bwlogin"]);
	}

	function IsLoggedIn()
	{
		return $this->_Authenticate();
	}

	function Login($user, $pass)
	{
		if($this->IsLoggedIn())
			return true;
		else
			return $this->_Authenticate($user, $pass);
	}
}

?>
