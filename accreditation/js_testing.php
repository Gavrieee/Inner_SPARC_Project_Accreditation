<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Toggle Div</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* This custom class allows smoother height transitions */
        .transition-max-height {
            transition: max-height 0.4s ease, opacity 0.4s ease, transform 0.4s ease;
        }
    </style>
    <script>
        function toggleDiv() {
            const content = document.getElementById("toggle-content");
            const button = document.getElementById("button-toggle");

            const isHidden = content.classList.contains("max-h-0");

            if (isHidden) {
                content.classList.remove("max-h-0", "opacity-0", "scale-95", "pointer-events-none");
                content.classList.add("max-h-40", "opacity-100", "scale-100");

                button.classList.remove("bg-none", "text-blue-600");
                button.classList.add("bg-blue-600", "text-white");
                button.textContent = "Hide Content";
            } else {
                content.classList.remove("max-h-40", "opacity-100", "scale-100");
                content.classList.add("max-h-0", "opacity-0", "scale-95", "pointer-events-none");

                button.classList.remove("bg-blue-600", "text-white");
                button.classList.add("bg-none", "text-blue-600");
                button.textContent = "Show Content";
            }
        }
    </script>
</head>

<body class="flex flex-col items-center justify-center min-h-screen bg-gray-100">

    <!-- Button -->
    <button onclick="toggleDiv()" id="button-toggle"
        class="bg-none border border-blue-600 hover:bg-blue-600 hover:text-white text-blue-600 font-bold py-2 px-4 rounded mb-4">
        Show Content
    </button>

    <!-- Toggling Div -->
    <div id="toggle-content"
        class="max-h-0 opacity-0 scale-95 pointer-events-none overflow-hidden transition-max-height bg-white p-4 rounded shadow w-64">
        👋 Hello! I'm now visible with animation and no space taken when hidden.
    </div>

</body>

</html>