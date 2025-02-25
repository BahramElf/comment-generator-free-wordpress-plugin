jQuery(document).ready(function ($) {

    // Function to toggle the visibility of the fields based on the comment mode selection
    function toggleFieldsVisibility() {
        var commentMode = $('input[name="wpex_comment_generator_comment_mode"]:checked').val();

        if (commentMode === 'single') {
            $('.single-mode-fields').show();
            $('.category-mode-fields').hide();
        } else if (commentMode === 'category') {
            $('.single-mode-fields').hide();
            $('.category-mode-fields').show();
        }
    }

    // Call the function initially to set the initial visibility
    toggleFieldsVisibility();

    // Bind change event to the comment mode selection
    $('input[name="wpex_comment_generator_comment_mode"]').on('change', function () {
        toggleFieldsVisibility();
    });

    // Function to handle the post type change event
    function handlePostTypeChange() {
        var selectedPostType = $("#post-type").val();
        var selectedCategory = $("#post-category").val();
        var commentMode = $('input[name="wpex_comment_generator_comment_mode"]:checked').val();
        var commentFromSelect = $(".comment-from");

        if (selectedPostType === "product") {
            commentFromSelect.show();
        } else {
            commentFromSelect.hide();
        }

        if (selectedPostType == "post") {
            $(".none-product-fields").show();
            $(".product-fields").hide();
            if (commentMode === 'category') {
                $('.category-mode-fields').show();
            }
        } else if (selectedPostType === "product") {
            $(".none-product-fields").hide();
            $(".product-fields").show();
            if (commentMode === 'category') {
                $('.category-mode-fields').show();
            }
        } else if (selectedPostType == "page") {
            $(".none-product-fields").show();
            $(".product-fields").hide();
            if (commentMode === 'category') {
                $('.category-mode-fields').show();
            }
        } else {
            $(".product-fields").hide();
            if (commentMode === 'category') {
                $('.category-mode-fields').show();
            }
        }

        populateCategories(selectedPostType, selectedCategory);
    }

    $("#post-type").change(function () {
        handlePostTypeChange();
    });

    handlePostTypeChange();

    function populateCategories(selectedPostType, selectedCategory) {
        var data = {
            action: "get_wpex_comment_generator_categories",
            post_type: selectedPostType,
            nonce: commentGeneratorSettings.nonce
        };
        $.get(ajaxurl, data, function (response) {
            var categorySelect = $("#post-category");
            categorySelect.empty();
            categorySelect.append("<option value='0'>-- Select Category --</option>");
            $.each(response, function (index, category) {
                categorySelect.append(
                    "<option value='" +
                    category.term_id +
                    "' " +
                    (category.term_id == selectedCategory ? "selected" : "") +
                    ">" +
                    category.name +
                    "</option>"
                );
            });
        });
    }

    // Populate categories on page load
    var selectedPostType = $("#post-type").val();
    var selectedCategory = wpex_comment_generator_data.selected_category; // Access the localized PHP variable value here
    populateCategories(selectedPostType, selectedCategory);

    function validateCommentForm() {

        var generalSentences = $.trim($('#wpex_comment_generator_general_sentences').val());
        var productBuyerSentences = $.trim($('#wpex_comment_generator_product_buyer_sentences').val());
        var productNonBuyerSentences = $.trim($('#wpex_comment_generator_product_non_buyer_sentences').val());
        var customAuthors = $.trim($('#wpex_comment_generator_custom_authors').val());
        var selectedPostType = $.trim($("#post-type").val());
        var commentFrom = $.trim($("#wpex_comment_generator_comment_from").val());

        if (selectedPostType !== "product" && (generalSentences == '' || customAuthors == '')) {
            alert(commentGeneratorSettings.emptyFieldNoProductMessage);
            return false;
        } else if (selectedPostType === "product" && commentFrom === "random" && (productBuyerSentences === '' || productNonBuyerSentences === '' || customAuthors === '')) {
            alert(commentGeneratorSettings.emptyFieldProductMessage);
            return false;
        } else if (selectedPostType === "product" && commentFrom === "buyer" && (productBuyerSentences === '' || customAuthors === '')) {
            alert(commentGeneratorSettings.emptyFieldProductBuyerMessage);
            return false;
        } else if (selectedPostType === "product" && commentFrom === "user" && (productNonBuyerSentences === '' || customAuthors === '')) {
            alert(commentGeneratorSettings.emptyFieldProductNoneBuyerMessage);
            return false;
        }

        return true;
    }

    // Attach the validation to the form submission
    $('#comment-generator-setting').submit(function () {
        return validateCommentForm();
    });

    $('#wpex_comment_generator_ajax_nonce-btn').on('click', function () {
        var data = {
            action: 'wpex_comment_generator_delete_commented_items',
            nonce: commentGeneratorSettings.nonce
        };

        $.post(ajaxurl, data, function (response) {
            if (response.success) {
                alert(commentGeneratorSettings.deleteSuccess);
            } else {
                alert(commentGeneratorSettings.deleteFailed);
            }
        }).fail(function (xhr, status, error) {
            console.error(error);
        });
    });

});