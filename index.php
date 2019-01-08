<?
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
  $file = '/tmp/sample-app.log';
  $message = file_get_contents('php://input');
  file_put_contents($file, date('Y-m-d H:i:s') . " Received message: " . $message . "\n", FILE_APPEND);
}
else
{
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <script type="text/javascript" src="js/lib/jquery.js"></script>
  <script type="text/javascript" src="js/lib/jquery.flot.js"></script>
  <script type="text/javascript" src="js/math.min.js"></script>
  <script type="text/javascript" src="https://d3js.org/d3.v3.min.js"></script>
  <script type="text/javascript" src="https://d3js.org/queue.v1.min.js"></script>
  <script type="text/javascript" src="https://d3js.org/topojson.v1.min.js"></script>
  <script type="text/javascript" src="js/settings.js"></script>
  <script type="text/javascript" src="js/lib/simple-slider.js"></script>
  <link   rel="stylesheet" type="text/css" href="css/lib/simple-slider.css"/> 
  <link   rel="stylesheet" type="text/css" href="css/main.css"/> 
  <title>COPEWELL Maps</title>
  <meta name="description" content="U.S. county maps of COPEWELL domains and response">
  <meta name="viewport" content="width=device-width, initial-scale=1.00">
</head>

<body onload="updateSlider()">
<div id="content">
  <div id="control">
    <script type="text/javascript">
        var upperSlider = 0;
        var lowerSlider = 0;
        sliderSetup(0,1)
        console.log(sliders)
    </script>
  </div>
</div>
<span id="display">  </span>

<script type="text/javascript">
  update("index");                        // too early for updating, calls settings.js version of update

  $("[data-slider]")
    .each(function () {
      var input = $(this);
      $("<span>")
        .addClass("output")
        .insertAfter($(this));
      console.log("$ data-slider")
    })

    .bind("slider:ready slider:changed", function (event, data) {
      $(this)
        .nextAll(".output:first")
        .html(data.value.toFixed(3));
    });
</script>

<table>
  <tr class="rowButtons">
    <td class="tdLabels">Uniform Event</td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="ResAbs_U" onclick="pushButton(this)">Resilience (abs)</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="ResRel_U" onclick="pushButton(this)">Resilience (rel)</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="Resistance_U" onclick="pushButton(this)">Resistance</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="Recovery_U" onclick="pushButton(this)">Recovery</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="PM_U"    onclick="pushButton(this)">PM</button></td>  <!--  Qi -->
    <td class="tdButtons"><button class="button" name="mapSelector" id="CFt_U" onclick="pushButton(this)">CF[Time]</button></td>
    <td class="tdLabels">Domains</td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="CF0"    onclick="pushButton(this)" title="Pre Community Functioning">CF0</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="PR"    onclick="pushButton(this)">PR</button></td>  <!--  Qi -->
    <td class="tdButtons"><button class="button" name="mapSelector" id="PVID"    onclick="pushButton(this)">PVID</button></td>  <!--  Qi -->
    <td class="tdButtons"><button class="button" name="mapSelector" id="SC"    onclick="pushButton(this)">SC</button></td>  <!--  Qi -->


  </tr>
  <tr class="rowButtons">
    <td class="tdLabels">Hurricane</td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="ResAbs_H" onclick="pushButton(this)">Resilience (abs)</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="ResRel_H" onclick="pushButton(this)">Resilience (rel)</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="Resistance_H" onclick="pushButton(this)">Resistance</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="Recovery_H" onclick="pushButton(this)">Recovery</button></td>
    <td class="tdButtons"><button class="button" name="mapSelector" id="PM_H"    onclick="pushButton(this)">PM</button></td>  <!--  Qi -->
    <td class="tdButtons"><button class="button" name="mapSelector" id="CFt_H" onclick="pushButton(this)">CF[Time]</button></td>
    <td class="tdControls"></td>
    <td class="tdControls"><button class="button" name="control"    id="FreezeScale" onclick="pushControl(this)">Freeze scale</button></td>
  </tr>
</table>
  


<div id="slidecontainer">
  <p>Time: <span id="demoTime"></span>
    <input type="range" min="0" max="199" value="199" class="slider" id="sliderTime">
    <button class="button" id="buttonCalculate" onclick="pushCal(this)">Calculate</button>
</div>


<script type="text/javascript">
  var maps = 0;
  var color_min = 1;
  var color_max = 0;
  var map
  var mapPrevious
  var eventType
  var calculateName
  var rsltUniform = {}
  var rsltHurricane = {}

  var color_domain     = [0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0]
  var ext_color_domain = [  0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0]
  var legend_labels    = ["0.20", "0.22", "0.24", "0.26", "0.28", "0.30", "0.32", "0.34", "0.36", "0.38", "0.40"]
  var color = d3.scale.threshold()
    .domain(color_domain)
    .range(["#FF2B00", "#FF5E00", "#FF9100", "#FFC400", "#FFF700", "#D5FF00", "#A2FF00", "#6FFF00", "#3CFF00", "#08FF00", "#00FF2B"]);

  var width  = 960,
      height = 500;

  var div = d3.select("body").append("div")   
    .attr("class", "tooltip")               
    .style("opacity", 0);

  var svg = d3.select("body").append("svg")
    .attr("width", width)
    .attr("height", height)
    .style("margin", "10px auto");

  var buttonCal = document.getElementById("buttonCalculate")
  buttonCal.style.backgroundColor = "green";
  var path = d3.geo.path()
  var rateById = {};
  var nameById = {};
  var ls_w = 20, ls_h = 20;


  updateSliderNew();
  //Reading map file and data

  pushButton(document.getElementById("CF0"));

  //Adding legend for our Choropleth

  var legend = svg.selectAll("g.legend")
    .data(ext_color_domain)
    .enter().append("g")
    .attr("class", "legend");

  //Called after slider adjustment

  function update(value,id){
    value = Number(value)
    console.log("update: " + value + id)
    if (!isNaN(value)) {
      if (id.indexOf("U") == 5) {
        map.mapMaxValue = value;
      } else {
        map.mapMinValue = value;
      }
    }

    var selectExample = d3.selectAll("path")
    selectExample.style("fill", function (d) { 
      return color ((rateById[d.id] - map.mapMinValue)/(map.mapMaxValue-map.mapMinValue+0.00001));
    });

    var j = NaN;
    var rows = document.getElementsByTagName("tr");
    for (i = 0; i < rows.length; i++){
      if (rows[i].id == "row" + map.mapName){
        j = i;
      } else {
        rows[i].bgColor = "white";
        rows[i].children[0].bgColor = "white";
        if (i>0){rows[i-1].children[0].bgColor = "white";}
      }
    }
    rows[j].bgColor = "yellow"
    let row_j_length = rows[j].children.length;
    if (row_j_length==5) {
      rows[j-1].children[0].bgColor = "yellow";
    }
    rows[j].children[0].bgColor = "yellow";
    rows[j].children[row_j_length-4].textContent = map.minValue.toFixed(3);
    rows[j].children[row_j_length-3].textContent = map.mapMinValue.toFixed(3);
    rows[j].children[row_j_length-2].textContent = map.mapMaxValue.toFixed(3);
    rows[j].children[row_j_length-1].textContent = map.maxValue.toFixed(3);

    if (upperSlider != 0 & lowerSlider != 0) {
      updateSlider();
    }
  }

  function updateSliderNew(){
    var sliderTime = document.getElementById("sliderTime");
    var outputTime = document.getElementById("demoTime");
    outputTime.innerHTML = (Number(sliderTime.value)*0.2).toFixed(2);
    sliderTime.oninput = function() {
      outputTime.innerHTML = (Number(this.value)*0.2).toFixed(2);
      
      if (calculateName.substr(0,3) == "CFt") {
        if (eventType == "Hurricane") {
          pushButton(document.getElementById("CFt_H"));
        }

        if (eventType == "Uniform") {
          pushButton(document.getElementById("CFt_U"));
        }

      }
    }
  }

  function updateSlider() {
    let mapTemp = map;
    if (document.getElementById("FreezeScale").innerHTML == "Unfreeze") {
      mapTemp = mapPrevious;
    };
    upperSlider.setRange(mapTemp.minValue*0.8,mapTemp.maxValue*1.2) //midValue
    upperSlider.setValue(mapTemp.mapMaxValue);
    lowerSlider.setRange(mapTemp.minValue*0.8,mapTemp.maxValue*1.2) //midValue
    lowerSlider.setValue(mapTemp.mapMinValue);   

    legend.text("")

    legend.append("rect")
      .attr("x", 10)
      .attr("y", function(d, i){ return height - (i*ls_h) - 2*ls_h - 30;})
      .attr("width", ls_w)
      .attr("height", ls_h)
      .style("fill", function(d, i) { return color(d); })
      .style("opacity", 0.8);
    legend.append("text")
      .attr("x", 40)
      .attr("y", function(d, i){ return height - (i*ls_h) - ls_h - 4 - 30;})
      .text(function(d, i){
        let label_i = map.mapMinValue + i/color_domain.length*(map.mapMaxValue-map.mapMinValue);
        return label_i.toFixed(2);
      });
  }

  //Executes after button selection


  function pushCal(button) {
    var buttonCal = document.getElementById("buttonCalculate")
    buttonCal.style.backgroundColor = "orange";
    buttonCal.innerHTML = "loading...";

    queue()
        .defer(d3.csv, maps[0].fileName)
        .defer(d3.csv, maps[1].fileName)
        .defer(d3.csv, maps[2].fileName)
        .defer(d3.csv, maps[3].fileName)
        .defer(d3.csv, maps[4].fileName)
        .defer(d3.csv, maps[5].fileName)
        .await(saveMapDataCalculcation);
  }

  function saveMapDataCalculcation(error, cf, pr, pvid, sc, pmUni, pmHur) {
    var scById = {}
    var pvidById = {}
    var prById = {}
    var cfById = {}
    var pmById = {}
    sc.forEach(function(d) {
      let d_value = Number(d.value)
      scById[d.id] = d_value
      nameById[d.id] = d.name;
    })
    pvid.forEach(function(d) {
      let d_value = Number(d.value)
      pvidById[d.id] = d_value
    })
    pr.forEach(function(d) {
      let d_value = Number(d.value)
      prById[d.id] = d_value
    })
    cf.forEach(function(d) {
      let d_value = Number(d.value)
      cfById[d.id] = d_value
    })
    pmUni.forEach(function(d) {
      let d_value = Number(d.value)
      pmById[d.id] = d_value
      //var_list = {'ER', 'CF', 'SC', 'PR', 'PreCF', 'PVID', 'PM'};
      var W0 = [0.5, cfById[d.id], scById[d.id],prById[d.id], cfById[d.id], pvidById[d.id],pmById[d.id]]
      var sol = solveODE(W0);
      // var rslt = calculate_results(sol);
      console.log(d.id)
      rsltUniform[d.id] = calculate_results(sol);
    })

    pmHur.forEach(function(d) {
      let d_value = Number(d.value)
      pmById[d.id] = d_value
      //var_list = {'ER', 'CF', 'SC', 'PR', 'PreCF', 'PVID', 'PM'};
      var W0 = [0.5, cfById[d.id], scById[d.id],prById[d.id], cfById[d.id], pvidById[d.id],pmById[d.id]]
      var sol = solveODE(W0);
      // var rslt = calculate_results(sol);
      rsltHurricane[d.id] = calculate_results(sol);
    })

    var buttonCal = document.getElementById("buttonCalculate")
    buttonCal.style.backgroundColor = "green";
    buttonCal.innerHTML = "Recalculate";
  }

  function pushControl(button) {
    if (button.id == "FreezeScale") {
      if (button.innerHTML == "Freeze scale") {
      button.innerHTML = "Unfreeze";
    button.style.backgroundColor = "orange";
    } else {
    button.innerHTML = "Freeze scale";
    button.style.backgroundColor = "#4CAF50";
    };
    };
    mapPrevious = map;
  }

  function pushButton(button){
    var buttons = document.getElementsByName("mapSelector")  //Change button colors
    for (i = 0; i < buttons.length; i++) {
      if (buttons[i].id == button.id) {
        buttons[i].style.color = "black";
        buttons[i].style.backgroundColor = "yellow";         //changes background-color
      } else {
        buttons[i].style.color = "white";
        buttons[i].style.backgroundColor = "#4CAF50";
      }
    }
    loadData(button.id)
  }

  function loadData(id){
    var initialRun = false;
    if (maps == 0) {
      initialRun = true;
      maps = [
        {mapName:"CF0",          fileName:"CF_0.csv"},
        {mapName:"PR",           fileName:"PR.csv"}, // Qi
        {mapName:"PVID",         fileName:"PVID.csv"}, // Qi
        {mapName:"SC",           fileName:"SC.csv"}, // Qi
        {mapName:"PM_U",         fileName:"genericPM.csv"}, // Qi
        {mapName:"PM_H",         fileName:"hurricanePM.csv"}, // Qi
        {mapName:"ResAbs_U",     fileName:"NaN.csv"},
        {mapName:"ResAbs_H",     fileName:"NaN.csv"},
        {mapName:"ResRel_U",     fileName:"NaN.csv"},
        {mapName:"ResRel_H",     fileName:"NaN.csv"},
        {mapName:"Resistance_U", fileName:"NaN.csv"},
        {mapName:"Resistance_H", fileName:"NaN.csv"},
        {mapName:"Recovery_U",   fileName:"NaN.csv"},
        {mapName:"Recovery_H",   fileName:"NaN.csv"},
        {mapName:"CFt_U",        fileName:"NaN.csv"},
        {mapName:"CFt_H",        fileName:"NaN.csv"}
      ]
      for (i = 0; i < maps.length; i++) {
        maps[i].minValue    = NaN; maps[i].maxValue    = NaN; maps[i].midValue = NaN;
        maps[i].mapMinValue = NaN; maps[i].mapMaxValue = NaN;
        maps[i].fileName    = "data/" + maps[i].fileName;
      }
    }

    var mapsCalculationUniform = [
        "ResAbs_U","ResRel_U","Resistance_U","Recovery_U","CFt_U"
    ]
    var mapsCalculationHurricane = [
        "ResAbs_H","ResRel_H","Resistance_H","Recovery_H","CFt_H"
    ]

    map = maps.find(item => item.mapName == id);


    if (initialRun) {
      queue()
        .defer(d3.json, "us.json")
        .defer(d3.csv, map.fileName)
        .await(ready); 
    } else {
      if (mapsCalculationUniform.includes(id)) {
        calculationsUniform(id)
      } else {
      if (mapsCalculationHurricane.includes(id)) {
        calculationsHurricane(id)
      }
      else {
      queue()
        .defer(d3.csv, map.fileName)
        .await(readyDataOnly);
        }
    }
    }
  }

  function calculationsUniform(id) {
    calculateName = chooseResult(id)
    queue()
        .defer(d3.csv, maps[4].fileName)
        .await(readyDataOnlyCalculationUniform);


  }
  function calculationsHurricane(id) {
    calculateName = chooseResult(id)
    queue()
        .defer(d3.csv, maps[5].fileName)
        .await(readyDataOnlyCalculationHurricane);

  }


  function chooseResult(id) {
    switch (id) {
      case "ResAbs_U": 
        return "absolute_resilience";
        break;
      case "ResAbs_H": 
        return "absolute_resilience";
        break;
      case "ResRel_U": 
        return "related_resilience";
        break;
      case "ResRel_H": 
        return "related_resilience";
        break;
      case "Resistance_U": 
        return "Resistance";
        break;
      case "Resistance_H": 
        return "Resistance";
        break;
      case "Recovery_U": 
        return "Recovery";
        break;
      case "Recovery_H": 
        return "Recovery";
        break;
      case "CFt_U": 
        var sliderTime = document.getElementById("sliderTime");
      var time = sliderTime.value;
      var str = "CFt[" + time + "]";
        return str;
        break;
      case "CFt_H": 
        var sliderTime = document.getElementById("sliderTime");
      var time = sliderTime.value;
      var str = "CFt[" + time + "]";
        return str;
        break;  
        
      default: return 0
    } 
  }



  //Used by function ready, before all of the setup

  function readyDataOnly(error, data) {
    saveMapData(data)
    update(NaN,"readyDataOnly")
  };

  function readyDataOnlyCalculationUniform(error, data) {
    eventType = "Uniform"
    getMapDataCalculcationUniform(data)
    update(NaN,"readyDataOnlyCalculationUniform")
  }; 

  function readyDataOnlyCalculationHurricane(error, data) {
    eventType = "Hurricane"
    getMapDataCalculcationHurricane(data)
    update(NaN,"readyDataOnlyCalculationHurricane")
  };

  //Used by function readyDataOnly

  function saveMapData(data) {
    color_min = 1;
    color_max = 0;
    data.forEach(function(d) {
      let d_value = Number(d.value)
      rateById[d.id] = d_value;
      nameById[d.id] = d.name;
      if (d_value < color_min) {color_min = d_value;}
      if (d_value > color_max) {color_max = d_value;}    
    })
    map.minValue = color_min;     //Use actual min/max from the data
    map.maxValue = color_max;
    map.midValue = (color_min + color_max) / 2;
    if (isNaN(map.mapMinValue)) {
      map.mapMinValue = color_min;
      map.mapMaxValue = color_max;
    }
  }
  

  function getMapDataCalculcationUniform(pm) {
    color_min = 1;
    color_max = 0;
    

    var pmById = {}
    
    pm.forEach(function(d) {
      nameById[d.id] = d.name;
      var cmd = "var d_value = rsltUniform[d.id]." + calculateName ;
      eval(cmd);
      rateById[d.id] = d_value
      if (d_value < color_min) {color_min = d_value;}
      if (d_value > color_max) {color_max = d_value;}    
    })
    map.minValue = color_min;     //Use actual min/max from the data
    map.maxValue = color_max;
    map.midValue = (color_min + color_max) / 2;
    if (isNaN(map.mapMinValue)) {
      map.mapMinValue = color_min;
      map.mapMaxValue = color_max;
    }
  }
  function getMapDataCalculcationHurricane(pm) {
    color_min = 1;
    color_max = 0;
    

    var pmById = {}
    
    pm.forEach(function(d) {
      nameById[d.id] = d.name;
      var cmd = "var d_value = rsltHurricane[d.id]." + calculateName ;
      eval(cmd);
      rateById[d.id] = d_value
      if (d_value < color_min) {color_min = d_value;}
      if (d_value > color_max) {color_max = d_value;}    
    })
    map.minValue = color_min;     //Use actual min/max from the data
    map.maxValue = color_max;
    map.midValue = (color_min + color_max) / 2;
    if (isNaN(map.mapMinValue)) {
      map.mapMinValue = color_min;
      map.mapMaxValue = color_max;
    }
  }

  //Start of Choropleth drawing

  function ready(error, us, data) {
      saveMapData(data)
      var slider = document.getElementById("inputLower");
      slider.setAttribute("value",map.mapMinValue);
      slider.setAttribute("data-slider-range",map.minValue + "," + map.maxValue); //midValue
      slider = document.getElementById("inputUpper")
      slider.setAttribute("value",map.mapMaxValue);
      slider.setAttribute("data-slider-range",map.minValue + "," + map.maxValue); //midValue
      update(NaN,"ready")

    //Drawing Choropleth

    svg.append("g")
      .attr("class", "region")
      .selectAll("path")
      .data(topojson.feature(us, us.objects.counties).features)
      .enter().append("path")
      .attr("d", path)
      .style ( "fill" , function (d) {                // d only contains id and map properties
        return color ((rateById[d.id] - color_min)/(color_max-color_min+0.0001));
      })
    
      .style("opacity", 1.0)  // 0.8

    //Adding mouseevents

      .on("mouseover", function(d) {
        d3.select(this).transition().duration(300).style("opacity", 0.5); // 1
        div.transition().duration(300)
          .style("opacity", 1)
        div.text(d.id + ", " + nameById[d.id] + " : " + Number(rateById[d.id]).toFixed(3))
          .style("left", (d3.event.pageX) + "px")//
          .style("top", (d3.event.pageY -30) + "px");
      })

      .on("mouseout", function() {
        d3.select(this)
          .transition().duration(300)
          .style("opacity", 1);  // 0.8
        div.transition().duration(300)
          .style("opacity", 0);
      })
  } // <-- End of Choropleth drawing

function calculate_results(sol){
  var rslt_single = {};
  var CF0 = sol.CF[0];
  rslt_single.CFt = sol.CF;
  var sum = 0;
  for( var i = 0; i < sol.CF.length; i++ ){
      sum += sol.CF[i]; //don't forget to add the base
  }

  var avg = sum/sol.CF.length;

  rslt_single.related_resilience = avg / CF0
  rslt_single.absolute_resilience = avg;

  var CFmin = Math.min.apply(Math, sol.CF);
  var Idxmin = sol.CF.indexOf(CFmin);

  rslt_single.Resistance = CFmin / CF0;
  // Find T half using bisection
  var CFhalf = 0.5 * (CFmin + CF0);
  var Idx1 = Idxmin;
  var Idx2 = sol.CF.length - 1;
  var Idx0 = Math.round( 0.5 * ( Idx1 + Idx2) );
  while(Idx2 - Idx1 > 1){
    Idx0 = Math.round( 0.5 * ( Idx1 + Idx2) );
    if(sol.CF[Idx0] == CFhalf){
      break;
    }else{
      if(sol.CF[Idx0] > CFhalf){
        Idx2 = Idx0;
      }else{
        Idx1 = Idx0;
      }
    }
  }
  rslt_single.tHalf = sol.tspan[Idx0];
  rslt_single.Recovery = 1 / rslt_single.tHalf;
  return rslt_single;

}

function solveODE(W0){
  var h = 0.1;
  // get initial value
  var CF = [];
  var tspan = [];
  var W = W0;
  var N = 200;
  
  for(var i=0; i < N; i++){
    CF[i] = W[1];
    var t = (i+1) * h;
    tspan[i] = t;

    var k1 = fdW(t, W);                                      // integrate one step using Runge-Kutta
    var k2 = fdW(t + h/2, aXbY(1,W,h/2,k1));
    var k3 = fdW(t + h/2, aXbY(1,W,h/2,k2));
    var k4 = fdW(t + h  , aXbY(1,W,h,k3));
    var temp = aXbY(1,k1, 2,k2)
    temp = aXbY(1,temp,2, k3)
    temp = aXbY(1,temp,1, k4)
    var W  = aXbY(1,W,  h/6, temp);
  }

  var sol = {};
  sol.CF = CF;
  sol.tspan = tspan;
  sol.dt = h;
  return sol;
}


function aXbY(a, X, b, Y){
  var Z = [];
  for(var i=0; i < X.length; i++){
    Z[i] = a * X[i] + b * Y[i];
  }
  return Z;
}

function fdW(t, W){
  var varlist = ['ER', 'CF', 'SC', 'PR', 'PreCF', 'PVID', 'PM'];

  for(var i=0; i < varlist.length; i++){
    var cmd = "var " + varlist[i] + " = W[" + i + "];" ;
    eval(cmd);
  }
  var Event0= 2;
  var alpha = 1;
  var beta  = 1/16.5;
  // constant variables
  var Event_damage_rate_constant = 2*Math.log(2);
  var ER_flow_rate_constant      = beta;              // beta = 0.0611 = 1/time scale = 1/16.4
  var PR_flow_rate_constant      = beta;
  var SC_flow_rate_constant      = beta;

  // CF replenish
  var CF_drop           = Math.max( PreCF - CF, 0 );
  var SC_flow_rate      = SC_flow_rate_constant * CF * SC * CF_drop;
  var PR_flow_rate      = PR_flow_rate_constant * PR      * CF_drop;
  var ER_flow_rate      = ER_flow_rate_constant * ER      * CF_drop;
  var CF_replenish_rate = SC_flow_rate + PR_flow_rate + ER_flow_rate;



// CF depletion rate
  var k       = Event_damage_rate_constant;
  var Event_t = Event0 * k * Math.exp(-k * t);

  var Event_damage_rate = Event_t * (2- PM - PVID)/2;
  var CF_depletion_rate = alpha  * CF * Event_damage_rate;  //alpha = 1
// Derivatives
  var dSC = - SC_flow_rate;
  var dPR = - PR_flow_rate;
  var dER = - ER_flow_rate;
  var dCF = - CF_depletion_rate + CF_replenish_rate ;
  var dPVID = 0
  var dPreCF = 0
  var dPM = 0

  var dW = [];
  for(var i=0; i < varlist.length; i++){
    var cmd = "dW[" + i + "] = d" + varlist[i] + ";";
    eval(cmd);
  }
  
  return dW;
}


</script>

<span>
  <table class="measuresTable">
    <tr>
      <th rowspan="2">Description</th><th rowspan="2">Event</th>
      <th colspan="2">Lower limit</th><th colspan="2">Upper limit</th>
    </tr>
    <tr>
      <th>(data)</th><th>(map)</th><th>(map)</th><th>(data)</th>
    </tr>
    <tr id="rowCF0">
      <td>CF0</td><td>---</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowPR"> <!--  Qi -->
      <td>PR</td><td>---</td><td></td><td></td><td></td><td></td> <!--  Qi -->
    </tr> <!--  Qi -->
    <tr id="rowPVID"> <!--  Qi -->
      <td>PVID</td><td>---</td><td></td><td></td><td></td><td></td> <!--  Qi -->
    </tr> <!--  Qi -->
    <tr id="rowSC"> <!--  Qi -->
      <td>SC</td><td>---</td><td></td><td></td><td></td><td></td> <!--  Qi -->
    </tr> <!--  Qi -->
    <tr id="rowPM_U"> <!--  Qi -->
      <td rowspan="2">PM</td><td>Uniform</td><td></td><td></td><td></td><td></td> <!--  Qi -->
    </tr> <!--  Qi -->
    <tr id="rowPM_H"> <!--  Qi -->
      <td>Hurricane</td><td></td><td></td><td></td><td></td> <!--  Qi -->
    </tr> <!--  Qi -->
    <tr id="rowResAbs_U">
      <td rowspan="2">Resilience (absolute)</td><td>Uniform</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowResAbs_H">
      <td>Hurricane</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowResRel_U">
      <td rowspan="2">Resilience (relative)</td><td>Uniform</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowResRel_H">
      <td>Hurricane</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowResistance_U">
      <td rowspan="2">Resistance</td><td>Uniform</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowResistance_H">
      <td>Hurricane</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowRecovery_U">
      <td rowspan="2">Recovery</td><td>Uniform</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowRecovery_H">
      <td>Hurricane</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowCFt_U">
      <td rowspan="2">CFt</td><td>Uniform</td><td></td><td></td><td></td><td></td>
    </tr>
    <tr id="rowCFt_H">
      <td>Hurricane</td><td></td><td></td><td></td><td></td>
    </tr>
  </table>
</span>

</body>

</html>

<? 
} 
?>
