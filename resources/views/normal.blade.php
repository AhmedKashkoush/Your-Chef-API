<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <table border= "1" align="center">

    <thead>
        <th>#</th>
        <th>name</th>
        <th>email</th>
        <th>phone</th>
        <th>verified at</th>
        <th>image</th>
    </thead>

    <tbody>
    @foreach ($users as $user)
    <tr align="center">
    <td>{{$user -> id}}</td>
    <td>{{$user -> name}}</td>
    <td>{{$user -> email}}</td>
    <td>{{$user -> phone}}</td>
    <td> <h3>{{$user -> verified_at??'unverified'}}</h3></td>
    <td>        <a href="{{ 
            asset(Storage::url($user->image))
        }}">        <img src= "{{ 
            asset(Storage::url($user->image))
        }}" width="100" height="100"></a></td>   
    @endforeach
    </tr>
    </tbody>
    </table>
</body>
</html>