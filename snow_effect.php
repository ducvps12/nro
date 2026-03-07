<!-- snow_effect.php -->
<style>
  body {
    position: relative;
    overflow: hidden;
    background: linear-gradient(to bottom, #001f3f, #003f7f); /* Màu nền mùa đông */
  }

  .snowflake {
    position: fixed;
    top: -10px;
    color: white;
    font-size: 1em;
    user-select: none;
    z-index: 9999;
    pointer-events: none;
    animation-name: fall, sway;
    animation-timing-function: linear, ease-in-out;
    animation-iteration-count: infinite, infinite;
  }

  /* Hiệu ứng rơi */
  @keyframes fall {
    0% {
      transform: translateY(-10px);
    }
    100% {
      transform: translateY(calc(100vh + 10px));
    }
  }

  /* Hiệu ứng lắc lư */
  @keyframes sway {
    0%, 100% {
      transform: translateX(0);
    }
    50% {
      transform: translateX(30px);
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const snowflakes = [];
    const snowflakeCount = 40; // Số lượng bông tuyết
    const snowflakeChars = ['❄', '❅', '❆']; // Ký tự tuyết
    const body = document.body;

    for (let i = 0; i < snowflakeCount; i++) {
      const snowflake = document.createElement('div');
      snowflake.className = 'snowflake';
      snowflake.innerText = snowflakeChars[Math.floor(Math.random() * snowflakeChars.length)];

      // Tọa độ ngẫu nhiên
      snowflake.style.left = Math.random() * window.innerWidth + 'px';
      snowflake.style.animationDuration = 5 + Math.random() * 10 + 's'; // Tốc độ rơi
      snowflake.style.animationDelay = Math.random() * 5 + 's'; // Trễ ngẫu nhiên
      snowflake.style.fontSize = 12 + Math.random() * 24 + 'px'; // Kích thước ngẫu nhiên
      snowflake.style.animationName = `fall, sway${Math.random() > 0.5 ? '-reverse' : ''}`; // Đảo ngẫu nhiên lắc lư

      body.appendChild(snowflake);
      snowflakes.push(snowflake);
    }
  });
</script>
