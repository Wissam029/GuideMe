<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Quiz Result</title>
</head>

<body>

<h2 id="score"></h2>
<p id="level"></p>

<script>
let score = parseInt(localStorage.getItem("score") || 0);
let total = parseInt(localStorage.getItem("total") || 1);

let percent = Math.round((score / total) * 100);

document.getElementById("score").innerText = `${score} / ${total}`;

let level = percent >= 80 ? "Excellent"
          : percent >= 50 ? "Good"
          : "Needs Improvement";

document.getElementById("level").innerText = level;
</script>

</body>
</html>