<!DOCTYPE html>
<html>
<head>
<title>Invoice Builder</title>
<style>
body { font-family: Arial; display:flex; gap:20px; padding:20px; }

#toolbox {
  width: 200px; border:1px solid #ccc; padding:10px;
}
.tool-item {
  padding: 8px;
  background:#eee;
  border:1px solid #ccc;
  margin-bottom:6px;
  cursor:pointer;
}

#canvas {
  flex:1;
  border:2px dashed #999;
  padding:10px;
  display:grid;
  grid-template-columns: repeat(6, 1fr);
  grid-auto-rows: minmax(60px, auto);
  gap:10px;
  position:relative;
}

.component {
  border:1px solid #444;
  background:white;
  padding:10px;
  cursor:move;
  resize:horizontal;
  overflow:auto;
}
.component[data-type="items-table"] {
  grid-column:1 / span 6 !important; /* Full width */
  resize:none;
}
button { margin-top:10px; }
</style>

<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
</head>
<body>

<div id="toolbox">
  <h4>Components</h4>
  <div class="tool-item" data-type="logo">Company Logo</div>
  <div class="tool-item" data-type="header">Invoice No + Date</div>
  <div class="tool-item" data-type="from">From Address</div>
  <div class="tool-item" data-type="to">To Address</div>
  <div class="tool-item" data-type="items-table">Items Table</div>
  <div class="tool-item" data-type="summary">Total Summary</div>

  <hr>
  <button onclick="saveTemplate()">💾 Save Template</button>
  <button onclick="togglePreview()">👁 Preview</button>
</div>

<div id="canvas"></div>

<script>
let canvas = document.getElementById('canvas');

document.querySelectorAll('.tool-item').forEach(el => {
  el.addEventListener('click', ()=> {
    let div = document.createElement('div');
    div.className = 'component';
    div.dataset.type = el.dataset.type;
    div.innerText = el.innerText;
    div.style.gridColumn = "span 2";
    canvas.appendChild(div);
    enableDrag(div);
  });
});

function enableDrag(element){
  interact(element).draggable({
    listeners:{
      move(event){
        element.style.transform =
        `translate(${event.dx}px, ${event.dy}px)`;
      },
      end(event){
        snapToGrid(element);
      }
    }
  }).resizable({
    edges:{ left:true, right:true },
    listeners:{
      move(event){
        element.style.width = event.rect.width + "px";
      }
    }
  });
}

function snapToGrid(el){
  el.style.transform = "translate(0,0)";
}

function serializeLayout(){
  let layout = [];
  canvas.querySelectorAll('.component').forEach(el=>{
    layout.push({
      type: el.dataset.type,
      col: el.style.gridColumn || "span 2"
    });
  });
  return layout;
}

function saveTemplate(){
  let name = prompt("Template Name?");
  if(!name) return;

  fetch("save_template.php", {
    method:"POST",
    headers:{"Content-Type":"application/json"},
    body:JSON.stringify({
      name: name,
      layout: serializeLayout()
    })
  }).then(r=>r.text()).then(alert);
}

function togglePreview(){
  window.open("preview.php","_blank");
}
</script>

</body>
</html>
