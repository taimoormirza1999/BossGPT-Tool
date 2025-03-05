 <!-- Include Toastify CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<button onclick="confirmAction()">Submit</button>

<script>
function confirmAction() {
    Toastify({
        text: "Click to confirm action",
        duration: 5000,
        close: true,
        gravity: "top",
        position: "center",
        backgroundColor: "linear-gradient(to right, #ff416c, #ff4b2b)",
        stopOnFocus: true,
        onClick: function(){
            if (confirm("Are you sure you want to proceed?")) {
                window.location.href = "process.php"; // PHP action
            }
        }
    }).showToast();
}
</script>
