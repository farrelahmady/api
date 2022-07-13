<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    <title>Email</title>
</head>

<body>
    <section style="font-family: 'Montserrat';display:flex;align-items:center;flex-direction:column;gap:1rem">
        <h1 style="font-size: 2.25rem;line-height:2.5rem;color:#2C96F1;font-weight:700">Forgot Password</h1>
        <h2 style="font-size: 1.25rem;line-height:1.75rem;font-weight:600;width:100%">
            Hello, {{ $user->profile->first_name }} {{ $user->profile->last_name }}
        </h2>

        <p>We have received to reset the password for Tailorine Account Associated with {{ $user->email }}. You
            can
            reset
            your
            password by clicking the link below</p>

        <form action="{{ $url }}" method="post">
            <input type="hidden" name="token">
            <input type="hidden" name="email">
            <button type="submit"
                style="background-color: #2C96F1;border-radius: 0.5rem;color:white;padding: 1rem 0.5rem; font-weight:500">Reset
                yout
                Password</button>
        </form>
    </section>
</body>

</html>
