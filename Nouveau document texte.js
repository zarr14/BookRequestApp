function showingsignup() {
  var welcomeDiv = document.querySelector(".welcome");
  var signupForm = document.getElementById("signupform");

  welcomeDiv.style.display = "none";
  signupForm.style.display = "block";
}

function showinlogin() {
  var welcomeDiv = document.querySelector(".welcome");
  var loginForm = document.getElementById("loginform");

  welcomeDiv.style.display = "none";
  loginForm.style.display = "block";
}

function validate(e) {
  e.preventDefault()
  var cin = document.getElementById("cin").value;

  if (cin.length === 0) {
    document.getElementById("spn1").innerHTML = "cin is required";
    isValid = false;
  }

  var password = document.getElementById("password").value;

  if (password.length === 0) {
    document.getElementById("spn6").innerHTML = "password is required";
    isValid = false;
  }

  var name = document.getElementById("nom").value;

  if (name.length === 0) {
    document.getElementById("spn2").innerHTML = "nom is required";
    isValid = false;
  }

  var prenom = document.getElementById("prenom").value;

  if (prenom.length === 0) {
    document.getElementById("spn3").innerHTML = "prenom is required";
    isValid = false;
  }

  var telephone = document.getElementById("tele").value;

  if (telephone.length === 0) {
    document.getElementById("spn5").innerHTML = "telephone is required";
    isValid = false;
  }

  var email = document.getElementById("email").value;

  if (email.length === 0) {
    document.getElementById("spn4").innerHTML = "email is required";
    isValid = false;
  } else if (!isValidEmail(email)) {
    document.getElementById("spn4").innerHTML = "EMAIL FORMAT INVALID";
    isValid = false;
  }
}
function isValidEmail(email) {
  return email.includes("@");
}





function validatelogin(e) {
  e.preventDefault();
  var email = document.getElementById("gmail").value;
  var password = document.getElementById("mot").value;
  var isValid = true;

  if (email.trim() === "") {
    document.getElementById("spn").innerHTML = "email required";
    isValid = false;
  } else {
    document.getElementById("spn").innerHTML = "";
  }

  if (password.trim() === "") {
    document.getElementById("spn1").innerHTML = "password required";
    isValid = false;
  } else {
    document.getElementById("spn1").innerHTML = "";
  }

  
}
