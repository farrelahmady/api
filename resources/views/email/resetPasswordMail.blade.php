<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>

<body>
    <h1>Forgot Password</h1>
    <h2>
        Hello {{ $user->profile->first_name }} {{ $user->profile->last_name }},
    </h2>
    <p>We have received to reset the password for Tailorine Account Associated with {{ $user->email }}. You can reset
        your
        password by clicking the link below</p>

    <a href="{{ $action_link }}">Reset your Password</a>

</body>

</html>
