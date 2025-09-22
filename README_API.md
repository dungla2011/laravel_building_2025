# Laravel Orion API Demo

Ứng dụng Laravel với gói Laravel Orion đã được tạo thành công!

## API Endpoints đã có sẵn:

### 1. Lấy danh sách users (GET)
```
GET http://127.0.0.1:8000/api/users
```

### 2. Tạo user mới (POST)
```
POST http://127.0.0.1:8000/api/users
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123"
}
```

### 3. Lấy thông tin user cụ thể (GET)
```
GET http://127.0.0.1:8000/api/users/{id}
```

### 4. Cập nhật user (PUT/PATCH)
```
PUT http://127.0.0.1:8000/api/users/{id}
Content-Type: application/json

{
    "name": "John Smith",
    "email": "johnsmith@example.com"
}
```

### 5. Xóa user (DELETE)
```
DELETE http://127.0.0.1:8000/api/users/{id}
```

### 6. Tìm kiếm users (POST)
```
POST http://127.0.0.1:8000/api/users/search
Content-Type: application/json

{
    "filters": [
        {
            "field": "name",
            "operator": "like",
            "value": "John"
        }
    ]
}
```

### 7. Tạo nhiều users cùng lúc (POST)
```
POST http://127.0.0.1:8000/api/users/batch
Content-Type: application/json

{
    "resources": [
        {
            "name": "User 1",
            "email": "user1@example.com",
            "password": "password123"
        },
        {
            "name": "User 2",
            "email": "user2@example.com",
            "password": "password123"
        }
    ]
}
```

### 8. Cập nhật nhiều users cùng lúc (PATCH)
```
PATCH http://127.0.0.1:8000/api/users/batch
Content-Type: application/json

{
    "resources": [
        {
            "id": 1,
            "name": "Updated User 1"
        },
        {
            "id": 2,
            "name": "Updated User 2"
        }
    ]
}
```

### 9. Xóa nhiều users cùng lúc (DELETE)
```
DELETE http://127.0.0.1:8000/api/users/batch
Content-Type: application/json

{
    "resources": [1, 2, 3]
}
```

## Cấu hình Database
- Database: PostgreSQL
- Host: 12.0.0.24:5432
- Database: monitor_v2
- Schema: glx_monitor_v2

## Cách sử dụng:
1. Server đang chạy tại: http://127.0.0.1:8000
2. Sử dụng Postman, Insomnia hoặc curl để test các API endpoints
3. Tất cả endpoints đều trả về dữ liệu JSON format

## Tính năng Laravel Orion:
- ✅ Tự động tạo CRUD API
- ✅ Filtering và searching
- ✅ Batch operations
- ✅ Pagination
- ✅ Sorting
- ✅ JSON API format