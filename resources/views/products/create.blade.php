<!DOCTYPE html>
<html>
<head>
    <title>Thêm sản phẩm</title>
</head>
<body>
    <h1>Thêm sản phẩm</h1>

    <form action="{{ route('products.store') }}" method="POST">
        @csrf
        <label for="name">Tên sản phẩm:</label>
        <input type="text" name="name" required><br>

        <label for="price">Giá:</label>
        <input type="number" name="price" required><br>

        <label for="description">Mô tả:</label>
        <textarea name="description"></textarea><br>

        <button type="submit">Thêm</button>
    </form>
    
    <a href="{{ route('products.index') }}">Quay lại danh sách</a>
</body>
</html>
