<!DOCTYPE html>
<html>
<head>
    <title>Danh sách sản phẩm</title>
</head>
<body>
    <h1>Danh sách sản phẩm</h1>
    <a href="{{ route('products.create') }}">Thêm sản phẩm</a>
    
    @if(session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <ul>
        @foreach ($products as $product)
            <li>
                {{ $product->name }} - {{ $product->price }}  VNĐ - {{ $product->description}}
                <a href="{{ route('products.edit', $product->id) }}">Sửa</a>
                <form action="{{ route('products.destroy', $product->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit">Xóa</button>
                </form>
            </li>
        @endforeach
    </ul>
</body>
</html>
