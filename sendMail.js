
function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(';');
  for(let i = 0; i <ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}

function parseJwt (token) {
  var base64Url = token.split('.')[1];
  var base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
  var jsonPayload = decodeURIComponent(window.atob(base64).split('').map(function(c) {
      return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
  }).join(''));

  return JSON.parse(jsonPayload);
}


function sendMail() {
  // get the id http://localhost:3000/cards/1420226221471433754
  cardId = window.location.href.split('/').pop();
  userID = parseJwt(getCookie('accessToken')).sub;

  // Create an XMLHttpRequest object
  const xhttp = new XMLHttpRequest();

  // Define a callback function
  xhttp.onload = function() {
    // Here you can use the Data
  }

  // Send a request
  url = `http://localhost:3000/?cardId=${cardId}&userID=${userID}`;
  console.log(url);
  //xhttp.open("GET", "ajax_info.txt");
  xhttp.send();
}
