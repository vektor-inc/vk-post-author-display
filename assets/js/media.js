document.addEventListener("DOMContentLoaded", function () {
    let customUploader;

    document.querySelectorAll(".media_btn").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            
            let mediaTarget = "#" + this.id.replace("media_", "");
            let thumbSrc = "#thumb_" + this.id.replace("media_", "");

            if (customUploader) {
                customUploader.open();
                return;
            }

            customUploader = wp.media({
                title: "Choose Image",
                library: { type: "image" },
                button: { text: "Choose Image" },
                multiple: false,
            });

            customUploader.on("select", function () {
                let images = customUploader.state().get("selection");
                images.each(function (file) {
                    document.querySelector(mediaTarget).value = file.toJSON().id;
                    document.querySelector(thumbSrc).src = file.toJSON().url;
                });
            });

            customUploader.open();
        });
    });

    document.querySelectorAll(".media_reset_btn").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            
            let mediaResetTarget = "#" + this.id.replace("media_reset_", "");
            let thumbSrc = "#thumb_" + this.id.replace("media_reset_", "");
            let defaultImageTarget = "#defaultImage_" + this.id.replace("media_reset_", "");

            let mediaField = document.querySelector(mediaResetTarget);
            if (mediaField) mediaField.value = "";

            let defaultImageSrc = document.querySelector(defaultImageTarget)?.src;
            if (defaultImageSrc) {
                let thumbImg = document.querySelector(thumbSrc);
                if (thumbImg) {
                    thumbImg.src = defaultImageSrc;
                    thumbImg.removeAttribute("srcset");
                }
            }
        });
    });
});