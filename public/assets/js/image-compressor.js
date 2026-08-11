/**
 * Automatic Client-Side Image Compression Engine
 * Intercepts image file inputs and compresses files before submission.
 */
document.addEventListener('DOMContentLoaded', function () {
    const fileInputs = document.querySelectorAll('input[type="file"]');

    fileInputs.forEach(input => {
        // Skip if input is explicitly marked as non-image
        if (input.accept && !input.accept.includes('image') && !input.accept.includes('png') && !input.accept.includes('jpg')) {
            return;
        }

        input.addEventListener('change', function (e) {
            const files = e.target.files;
            if (!files || files.length === 0) return;

            const file = files[0];
            // Only process image files > 300KB
            if (!file.type.startsWith('image/') || file.size < 300 * 1024) {
                return;
            }

            compressImageFile(file, 1400, 0.85, function (compressedBlob) {
                if (!compressedBlob) return;

                const ext = compressedBlob.type === 'image/webp' ? '.webp' : '.jpg';
                const newName = file.name.replace(/\.[^/.]+$/, "") + '_opt' + ext;
                const newFile = new File([compressedBlob], newName, { type: compressedBlob.type, lastModified: Date.now() });

                // Replace input files using DataTransfer API
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(newFile);
                input.files = dataTransfer.files;

                console.log(`[ImageCompressor] Successfully compressed '${file.name}' (${(file.size / 1024 / 1024).toFixed(2)} MB -> ${(newFile.size / 1024).toFixed(2)} KB)`);
            });
        });
    });

    function compressImageFile(file, maxDim, quality, callback) {
        const reader = new FileReader();
        reader.onload = function (event) {
            const img = new Image();
            img.onload = function () {
                let width = img.width;
                let height = img.height;

                if (width > maxDim || height > maxDim) {
                    if (width >= height) {
                        height = Math.round((height / width) * maxDim);
                        width = maxDim;
                    } else {
                        width = Math.round((width / height) * maxDim);
                        height = maxDim;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                // Use smooth quality interpolation
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, width, height);

                // Prefer webp, fallback to jpeg
                const mimeType = 'image/webp';
                canvas.toBlob(function (blob) {
                    if (blob) {
                        callback(blob);
                    } else {
                        canvas.toBlob(function (fallbackBlob) {
                            callback(fallbackBlob);
                        }, 'image/jpeg', quality);
                    }
                }, mimeType, quality);
            };
            img.src = event.target.result;
        };
        reader.readAsDataURL(file);
    }
});
