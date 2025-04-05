<!DOCTYPE html>
<html>
<head>
    <title>Danh sách sản phẩm</title>
</head>
<body>
<h1>Danh sách sản phẩm</h1>
<ul>
    @foreach ($products as $product)
        <li>{{ $product->name }} - {{ $product->price }} VNĐ</li>
    @endforeach
</ul>

<h2>Thêm sản phẩm</h2>
<form method="POST" action="/products">
    @csrf
    <input type="text" name="name" placeholder="Tên sản phẩm">
    <input type="text" name="price" placeholder="Giá">
    <button type="submit">Thêm</button>
</form>

</body>
</html>
