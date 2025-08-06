<?php
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Đăng bài mới';
include 'includes/header.php';
include 'includes/navbar.php';
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-4">
                        <i class="fas fa-plus"></i> Đăng bài mới
                    </h4>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
                    <?php endif; ?>
                    
                    <form action="functions/posts.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="create_post">
                        
                        <div class="d-flex mb-3">
                            <img src="<?php echo $_SESSION['avatar'] ?? 'assets/images/default-avatar.jpg'; ?>" 
                                 alt="Avatar" class="avatar me-3">
                            <div class="flex-grow-1">
                                <textarea class="form-control" name="content" rows="4" 
                                          placeholder="Bạn đang nghĩ gì? Hãy chia sẻ với mọi người... (Sử dụng #hashtag để phân loại bài viết)" 
                                          required></textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="image" class="form-label">
                                    <i class="fas fa-image"></i> Hình ảnh (tùy chọn)
                                </label>
                                <input type="file" class="form-control" id="image" name="image" 
                                       accept="image/jpeg,image/png,image/gif" onchange="previewImage(this)">
                                <small class="text-muted">Hỗ trợ JPG, PNG, GIF. Tối đa 5MB.</small>
                                <div id="imagePreview" class="mt-2"></div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="audio" class="form-label">
                                    <i class="fas fa-music"></i> Âm thanh (tùy chọn)
                                </label>
                                <input type="file" class="form-control" id="audio" name="audio" 
                                       accept="audio/mpeg,audio/wav,audio/ogg" onchange="previewAudio(this)">
                                <small class="text-muted">Hỗ trợ MP3, WAV, OGG. Tối đa 10MB.</small>
                                <div id="audioPreview" class="mt-2"></div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="text-muted">
                                <small>
                                    <i class="fas fa-info-circle"></i> 
                                    Mẹo: Sử dụng #hashtag để bài viết của bạn dễ được tìm thấy hơn
                                </small>
                            </div>
                            <div>
                                <button type="button" class="btn btn-secondary me-2" onclick="clearForm()">
                                    <i class="fas fa-eraser"></i> Xóa
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Đăng bài
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tips card -->
            <div class="card mt-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-lightbulb text-warning"></i> Mẹo để có bài viết hay
                    </h6>
                    <ul class="mb-0">
                        <li>Sử dụng hashtag (#) để phân loại nội dung</li>
                        <li>Chia sẻ những suy nghĩ tích cực và có ý nghĩa</li>
                        <li>Kèm theo hình ảnh hoặc âm thanh để bài viết sinh động hơn</li>
                        <li>Tương tác với bài viết của người khác để xây dựng cộng đồng</li>
                        <li>Tránh spam và nội dung không phù hợp</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'img-fluid rounded';
            img.style.maxHeight = '200px';
            preview.appendChild(img);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewAudio(input) {
    const preview = document.getElementById('audioPreview');
    preview.innerHTML = '';
    
    if (input.files && input.files[0]) {
        const audio = document.createElement('audio');
        audio.controls = true;
        audio.className = 'w-100';
        audio.src = URL.createObjectURL(input.files[0]);
        preview.appendChild(audio);
    }
}

function clearForm() {
    if (confirm('Bạn có chắc chắn muốn xóa nội dung đã nhập?')) {
        document.querySelector('form').reset();
        document.getElementById('imagePreview').innerHTML = '';
        document.getElementById('audioPreview').innerHTML = '';
    }
}

// Auto-resize textarea
document.querySelector('textarea').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Character counter
const textarea = document.querySelector('textarea');
const maxLength = 1000;

textarea.addEventListener('input', function() {
    const remaining = maxLength - this.value.length;
    let counter = document.getElementById('charCounter');
    
    if (!counter) {
        counter = document.createElement('small');
        counter.id = 'charCounter';
        counter.className = 'text-muted';
        this.parentNode.appendChild(counter);
    }
    
    counter.textContent = `${this.value.length}/${maxLength} ký tự`;
    
    if (remaining < 0) {
        counter.className = 'text-danger';
        this.setCustomValidity('Nội dung quá dài');
    } else {
        counter.className = 'text-muted';
        this.setCustomValidity('');
    }
});
</script>

<?php include 'includes/footer.php'; ?>
