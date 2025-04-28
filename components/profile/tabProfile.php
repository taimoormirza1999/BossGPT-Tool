<div class="tab-pane fade show active" id="profile" role="tabpanel" aria-labelledby="profile-tab">
    <h2 class="text-center text-white font-secondaryBold">Profile</h2>
    <div class="mt-3">
        <div class="text-center">
            <img id="avatarPreview" src="<?= $_SESSION['avatar_image'] ?? $images['default-user-image'] ?>" alt="Avatar"
                class="rounded-circle mb-2" width="100" height="100">
            <input type="file" id="avatarImage" accept="image/*">
        </div>
    </div>
    <form id="profileForm" class="text-white" enctype="multipart/form-data" method="POST">

        <div class="mb-3">
            <label for="profileUserName" class="form-label">Username<?php ?></label>
            <input type="text" class="form-control" id="profileUserName" placeholder="Zeeshanali96"
                value="<?php echo $_SESSION['username'] ?? ''; ?>">
        </div>
        <div class="mb-3">
            <label for="profileName" class="form-label">Name<?php ?></label>
            <input type="text" class="form-control" id="profileName" placeholder="Enter full name"
                value="<?php echo $_SESSION['name'] ?? ''; ?>">
        </div>
        <div class="mb-3">
            <label for="profileEmail" class="form-label">Email<?php ?></label>
            <input type="email" class="form-control text-lowercase text-white" id="profileEmail"
                placeholder="user@example.com" value="<?php echo $_SESSION['email'] ?? ''; ?>">
        </div>
        <div class="mb-3">
            <label for="profileBio" class="form-label">Bio</label>
            <textarea class="form-control" id="profileBio" rows="3" placeholder="Tell us about yourself..."
                value="<?php echo $_SESSION['bio'] ?? ''; ?>"></textarea>
        </div>
        <div class="mb-4 d-flex justify-content-between items-center">
            <!-- <login with google button -->
            <button class="btn btn-main-primary" type="button" id="loginWithGoogle">
                <?= getGoogleIcon() ?> Link your Google Account
            </button>
            <button class="btn  btn-main-primary" type="submit">Update</button>
        </div>
    </form>
    <script>
        // Clicking avatar triggers file input
        document.getElementById('avatarPreview').addEventListener('click', function () {
            document.getElementById('avatarImage').click();
        });

        document.getElementById('avatarImage').addEventListener('change', async function () {
            const file = this.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('avatar', file);

            try {
                const res = await fetch('?api=upload_profile_image', {
                    method: 'POST',
                    body: formData
                });

                const data = await res.json();

                if (res.ok && data.success) {
                    // Update avatar preview immediately
                    document.getElementById('avatarPreview').src = data.image_url;

                    iziToast.success({
                        title: 'Success',
                        message: 'Profile image updated!'
                    });
                } else {
                    iziToast.error({
                        title: 'Error',
                        message: data.message || 'Upload failed'
                    });
                }
            } catch (err) {
                iziToast.error({
                    title: 'Error',
                    message: 'Network error or server is offline.'
                });
                console.log(err);
            }
        });

    </script>
</div>