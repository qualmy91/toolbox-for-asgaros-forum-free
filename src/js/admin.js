window.addEventListener("load", function () {

    // check if tfaf page
    let wrapper = document.querySelector("div.tfaf_admin_wrapper");

    if (!wrapper) {return;}

    //store tabs
    let tabs = document.querySelectorAll("ul.nav-tabs > li");
    let i = 0;
    for (i = 0; i < tabs.length; i++) {
        tabs[i].addEventListener("click", switchTab);
    }

    function switchTab(event) {
        event.preventDefault();

        document.querySelector("ul.nav-tabs li.active").classList.remove("active");
        document.querySelector(".tab-pane.active").classList.remove("active");

        let clickedTab = event.currentTarget;
        let anchor = event.target;
        let activePaneID = anchor.getAttribute("href");

        clickedTab.classList.add("active");
        document.querySelector(activePaneID).classList.add("active");
    }

    let up_buttons = document.querySelectorAll("button.up_button");
    for (i = 0; i < up_buttons.length; i++) {
        up_buttons[i].addEventListener("click", moveRowUp);
    }

    function moveRowUp() {
        let row = this.parentNode.parentNode,
            table = row.parentNode,
            sibling = row.previousElementSibling,
            nextTagName = sibling.childNodes[0].tagName;

        if (!(nextTagName === "TH")) {
            this.parentNode.parentElement.classList.add("hide");

            row.addEventListener('transitionend', function eventWrapper() {
                table.insertBefore(row, sibling);
                updatePositions(table);
                row.removeEventListener('transitionend', eventWrapper, false);
                setTimeout(() => {
                    row.firstChild.parentElement.classList.remove("hide");
                }, 200)

            }, false);

        }

    }

    let down_buttons = document.querySelectorAll("button.down_button");
    for (i = 0; i < down_buttons.length; i++) {
        down_buttons[i].addEventListener("click", moveRowDown);
    }

    function moveRowDown() {
        let row = this.parentNode.parentNode,
            sibling = row.nextElementSibling,
            table = row.parentNode

        if (sibling != null) {
            this.parentNode.parentElement.classList.add("hide");

            row.addEventListener('transitionend', function eventWrapper() {
                table.insertBefore(sibling, row);
                updatePositions(table);
                row.removeEventListener('transitionend', eventWrapper, false);
                setTimeout(() => {
                    row.firstChild.parentElement.classList.remove("hide");
                }, 100)

            }, false);
        }
    }

    function updatePositions(table) {
        let rows = [].slice.call(table.childNodes, 1);
        let positions = '';
        rows.forEach(rows => positions += rows.childNodes[0].textContent + "-");
        document.getElementById("tfaf_menu_positions").value = positions;

        // Change table heading
        table.childNodes[0].childNodes[0].textContent = "Old Position";

        // Activate save order button
        document.getElementById('tfaf-button-save-order').style.display = "inline";

    }
});

jQuery(document).ready(function ($) {

    $(document).on('click', '.js-image-upload', function (e) {
        e.preventDefault();
        let $button = $(this);

        let file_frame = wp.media.frames.file_frame = wp.media({
            title: 'Select or upload an Image',
            library: {
                type: 'image'
            },
            button: {
                text: 'Select Image'
            },
            multiple: false
        });

        file_frame.on('select', function () {
            let attachment = file_frame.state().get('selection').first().toJSON();
            $button.siblings('.image-upload').val(attachment.url);
        });

        file_frame.open();

    });

});