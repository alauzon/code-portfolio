$(function(){
  if (sessionStorage.getItem("message")) {
      console.log(sessionStorage.getItem("message"));
      $('#messages').text(sessionStorage.getItem("message"));
      sessionStorage.removeItem("message");
  } else {
      console.log("Session message does not exist.");
  }
});
