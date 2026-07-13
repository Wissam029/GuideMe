<style>
#chatFloatingBtn{
  position: fixed;
  right: 24px;
  bottom: 24px;

  width: 62px;
  height: 62px;
  border-radius: 50%;

  background: #65435c;
  color: white;
  border: none;

  font-size: 26px;
  cursor: pointer;

  box-shadow: 0 12px 28px rgba(101,67,92,.28);

  z-index: 999999;

  display: flex;
  align-items: center;
  justify-content: center;

  text-decoration: none;
}

#chatFloatingBtn:hover{
  background: #54364d;
  transform: translateY(-2px);
}

@media(max-width: 500px){
  #chatFloatingBtn{
    right: 16px;
    bottom: 18px;
  }
}
</style>

<a href="/guideme1/guideme/phpmailer-master/career_guidance_bot/career_guidance_bot.php" id="chatFloatingBtn">
  💬
</a>