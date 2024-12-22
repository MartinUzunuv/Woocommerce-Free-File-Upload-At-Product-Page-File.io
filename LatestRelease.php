// Display a file upload field conditionally at the product page
add_action('woocommerce_before_add_to_cart_button', 'simple_video_selector');
function simple_video_selector() {
    echo '<div class="custom-video-option">';
    echo '<select id="video-fajl" name="video_option">';
    echo '<option value="none">Без видео</option>';
    echo '<option value="video">С видео</option>';
    echo '</select>';
    echo '</div>';

    echo '<div class="custom-file-upload" id="fileUploadWrapper" style="display: none;">';
    echo '<label for="fileInput">Upload a file for this product:</label>';
    echo '<input type="file" id="fileInput" />';
    echo '<div id="uploadStatus"></div>';
    echo '<div id="filePreview"></div>';
    echo '<textarea id="custom_note" name="custom_note" hidden></textarea>';
    echo '</div>';
    ?>
    <script>
        const videoOptionSelector = document.getElementById("video-fajl");
        const fileUploadWrapper = document.getElementById("fileUploadWrapper");
        const fileInput = document.getElementById("fileInput");

        // Show/hide the file upload field based on the selector value
        videoOptionSelector.addEventListener("change", () => {
            if (videoOptionSelector.value === "video") {
                fileUploadWrapper.style.display = "block";
            } else {
                fileUploadWrapper.style.display = "none";
                fileInput.value = ""; // Clear the file input field
                document.getElementById("uploadStatus").textContent = ""; // Clear upload status
                document.getElementById("filePreview").innerHTML = ""; // Clear file preview
            }
        });

        // Upload file automatically when selected
        async function uploadFile(event) {
            const fileInput = event.target;
            const uploadStatus = document.getElementById("uploadStatus");
            const customNoteField = document.getElementById("custom_note");
            const filePreview = document.getElementById("filePreview");

            if (fileInput.files.length === 0) {
                uploadStatus.textContent = "Please select a file.";
                return;
            }

            const file = fileInput.files[0];
            uploadStatus.textContent = "Uploading...";

            // Clear previous preview
            filePreview.innerHTML = "";

            // Display video preview if the file is a video
            if (file.type.startsWith("video/")) {
                const videoElement = document.createElement("video");
                videoElement.controls = true;
                videoElement.width = 300; // Set desired width
                videoElement.src = URL.createObjectURL(file);
                filePreview.appendChild(videoElement);
            }

            const formData = new FormData();
            formData.append("file", file);

            try {
                const response = await fetch("https://file.io", {
                    method: "POST",
                    body: formData,
                });

                if (!response.ok) {
                    uploadStatus.textContent = `Upload failed: ${response.status} ${response.statusText}`;
                    return;
                }

                const result = await response.json();
                const fileLink = result.link;

                // Set the hidden note field's value to the uploaded file's link
                customNoteField.value = fileLink;

                uploadStatus.textContent = `File uploaded! Link saved.`;
            } catch (error) {
                uploadStatus.textContent = "Error during upload. Please try again.";
                console.error("Error during upload:", error);
            }
        }

        // Attach file input change event listener
        fileInput.addEventListener("change", uploadFile);
    </script>
    <?php
}

// Validate and save the custom note field value
add_filter('woocommerce_add_to_cart_validation', 'validate_simple_video_selector', 10, 3);
function validate_simple_video_selector($passed, $product_id, $quantity) {
    if (isset($_POST['video_option']) && $_POST['video_option'] === 'video' && empty($_POST['custom_note'])) {
        wc_add_notice(__('Please upload a file before adding to cart.', 'woocommerce'), 'error');
        $passed = false;
    }
    return $passed;
}

add_filter('woocommerce_add_cart_item_data', 'save_simple_video_selector', 10, 2);
function save_simple_video_selector($cart_item_data, $product_id) {
    if (isset($_POST['custom_note'])) {
        $cart_item_data['custom_note'] = sanitize_text_field($_POST['custom_note']);
    }
    return $cart_item_data;
}

// Display the custom note in the cart
add_filter('woocommerce_get_item_data', 'display_simple_video_selector_in_cart', 10, 2);
function display_simple_video_selector_in_cart($item_data, $cart_item) {
    if (isset($cart_item['custom_note'])) {
        $item_data[] = array(
            'key'   => __('File Link', 'woocommerce'),
            'value' => '<a href="' . esc_url($cart_item['custom_note']) . '" target="_blank">Download File</a>',
        );
    }
    return $item_data;
}

// Save the custom note to order metadata
add_action('woocommerce_checkout_create_order_line_item', 'save_simple_video_selector_to_order', 10, 4);
function save_simple_video_selector_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['custom_note'])) {
        $item->add_meta_data(__('File Link', 'woocommerce'), $values['custom_note'], true);
    }
}
