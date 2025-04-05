<!DOCTYPE html>
<html>
<head>
    <title>Sửa sản phẩm</title>
</head>
<body>
    <h1>Sửa sản phẩm</h1>

    <form action="{{ route('products.update', $product->id) }}" method="POST">
        @csrf
        @method('PUT')

        <label for="name">Tên sản phẩm:</label>
        <input type="text" name="name" value="{{ $product->name }}" required><br>

        <label for="price">Giá:</label>
        <input type="number" name="price" value="{{ $product->price }}" required><br>

        <label for="description">Mô tả:</label>
        <textarea name="description">{{ $product->description }}</textarea><br>

        <button type="submit">Cập nhật</button>
    </form>

    <a href="{{ route('products.index') }}">Quay lại danh sách</a>
</body>
</html>
