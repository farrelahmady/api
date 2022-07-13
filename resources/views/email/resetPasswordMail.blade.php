<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">
    @vite('resources/css/app.css')
    <title>Document</title>
</head>

<body>
    <section class="py-3 px-6 flex items-center flex-col gap-4 font-montserrat">
        <h1 class="text-4xl text-[#2C96F1] font-bold ">Forgot Password</h1>
        <img src="logo.png" alt="tailorine" class="w-20 rounded-lg">
        <h2 class="text-xl font-semibold w-full">
            Hello, {{ $user->profile->first_name }} {{ $user->profile->last_name }}
        </h2>

        <p>We have received to reset the password for Tailorine Account Associated with {{ $user->email }}. You can
            reset
            your
            password by clicking the link below</p>

        <form action="{{ $url }}" method="post">
            <input type="hidden" name="token">
            <input type="hidden" name="email">
            <button type="submit" class="bg-[#2C96F1] rounded-lg text-white px-4 py-2 mt-4 font-medium">Reset yout
                Password</button>
        </form>
    </section>
</body>

</html>
