// only used in the beginning
var sliders = [
  ["Lower", 0.25, "Lower value", 0, 0.5],
  ["Upper", 0.75, "Upper value", 0.5, 1],
];

function sliderSetup(lowerValue,upperValue){
  for(i = 0; i < sliders.length; i++){
    echo_slider(sliders[i],lowerValue,upperValue);
    if(i%2 == 1){document.write("</br>");}
    console.log(i)
  }
}

function update(msg){
// necessary (called elsewhere)
  console.log("update in settings: " + msg)
}

function echo_slider(entry,lowerValue,upperValue){
  var id = entry[0];
  var value = Number(entry[1]);
  var desc = entry[2];
  var middleValue = (lowerValue + upperValue)/2;
  console.log(id)
  if (id.indexOf("U") == 0) {
	var attr = " type='text' onchange='update(value,id);' data-slider=true data-slider-highlight=true data-slider-range='" + middleValue + "," + upperValue + "'";
  } else {
	var attr = " type='text' onchange='update(value,id);' data-slider=true data-slider-highlight=true data-slider-range='" + lowerValue + "," + middleValue + "'"; 
  }
  var str = "<span class='variable'><span class='variableID' title='" + desc + "'>" + id + ": </span> <input id='input" + id + "' value= " + value + attr + "> </span>";
  console.log(str)
  document.write(str);
}
