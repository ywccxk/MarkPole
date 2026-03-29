// 全局变量
let map;
// 使用CONFIG（如果存在则使用，否则使用默认值）
var zoom = (typeof CONFIG !== 'undefined' && CONFIG.zoom) ? CONFIG.zoom : 18;
var defaultLng = (typeof CONFIG !== 'undefined' && CONFIG.centerLng) ? CONFIG.centerLng : 119.28188;
var defaultLat = (typeof CONFIG !== 'undefined' && CONFIG.centerLat) ? CONFIG.centerLat : 26.64158;
var imgBaseUrl = (typeof CONFIG !== 'undefined' && CONFIG.imgBaseUrl) ? CONFIG.imgBaseUrl : '';

let datas = [];
let allDatas = []; // 保存原始数据
let deviceFilterActive = false; // 设备筛选状态

// 信息窗口状态
let currentInfoWindow = {
    content: '',
    lnglat: null,
    isOpen: false
};

// DOM元素缓存
const formContainer = document.getElementById('form-container');
const formFields = ['polename', 'upperpole', 'disconnectswitch', 'circuitbreaker', 'bareconductor', 'longitude', 'latitude', 'address', 'remark'];

// 获取图片标签（优先缩略图，失败则原图）
function getImgTag(imgUrl) {
    if (!imgUrl) return '';
    const safeImgUrl = imgUrl.replace(/#/g, '%23');
    const baseUrl = imgBaseUrl || '';
    const thumbUrl = baseUrl + '/img/thumb/' + safeImgUrl;
    const originalUrl = baseUrl + '/img/' + safeImgUrl;
    return `<a href='${originalUrl}' target='_blank'><img style='float:left;margin:5px -15px' src='${thumbUrl}' width='120' height='135' onerror="this.src='${originalUrl}'"/></a>`;
}

// 地图初始化 - 直接使用天地图API
function onLoad() {
    if (typeof T === 'undefined' || !T.Map) {
        console.error('天地图API加载失败，请检查网络连接');
        alert('天地图API加载失败，请刷新页面或检查网络连接');
        return;
    }

    fetch('./phpapi/config_api.php')
        .then(res => res.json())
        .then(configData => {
            const tk = configData?.data?.tianditu?.tk || '';
            const imageURL = `http://t0.tianditu.gov.cn/img_w/wmts?SERVICE=WMTS&REQUEST=GetTile&VERSION=1.0.0&LAYER=img&STYLE=default&TILEMATRIXSET=w&FORMAT=tiles&TILEMATRIX={z}&TILEROW={y}&TILECOL={x}&tk=${tk}`;
            initMap(imageURL);
        })
        .catch(err => {
            console.error('获取配置失败:', err);
            initMap('');
        });
}

function initMap(imageURL) {
    const lay = new T.TileLayer(imageURL, { minZoom: 1, maxZoom: 18 });
    map = new T.Map("mapDiv", { layers: [lay] });
    map.centerAndZoom(new T.LngLat(defaultLng, defaultLat), zoom);
    map.enableScrollWheelZoom();
    map.addEventListener("click", MapClick);
    map.addControl(new T.Control.MapType());
    map.addControl(new T.Control.Scale());
    map.enableKeyboard();
}

// 表单操作工具函数
function clearForm() {
    formFields.forEach(field => {
        const el = document.getElementById(field);
        if (el) el.value = '';
    });
    document.getElementById('fileInput').value = null;
}

function fillForm(data) {
    const fieldMap = {
        polename: 'PoleName',
        upperpole: 'UpperPole',
        disconnectswitch: 'DisconnectSwitch',
        circuitbreaker: 'CircuitBreaker',
        bareconductor: 'BareConductor',
        longitude: 'longitude',
        latitude: 'latitude',
        address: 'Address',
        remark: 'Note'
    };
    
    formFields.forEach(field => {
        const el = document.getElementById(field);
        if (el && data[fieldMap[field]] !== undefined) {
            el.value = data[fieldMap[field]];
        }
    });
    document.getElementById('fileInput').value = null;
}

function toggleForm(show) {
    formContainer.style.right = show ? '0' : '-350px';
}

// 生成信息窗口HTML内容（公共函数）
function createInfoContent(item, index) {
    return `<div style='margin:0px;'>
        <div style='margin:10px 10px;'>
            ${getImgTag(item.ImgUrl)}
            <div style='margin:10px 0px 10px 120px;' onClick='infoClicked(${index})'>
                杆号：${item.PoleName}<br>
                上级杆：${item.UpperPole}<br>
                刀闸：${item.DisconnectSwitch}<br>
                开关：${item.CircuitBreaker}<br>
                裸导：${item.BareConductor}<br>
                经度：${item.longitude}<br>
                纬度：${item.latitude}<br>
                地址：${item.Address}<br>
                备注：${item.Note}
            </div>
        </div>
    </div>`;
}

// 更新地图上已有的标记点
function updateMarkerOnMap(index, data) {
    map.clearOverLays();
    
    datas.forEach((item, idx) => {
        const point = new T.LngLat(item.longitude, item.latitude);
        const marker = new T.Marker(point);
        map.addOverLay(marker);
        addClickHandler(createInfoContent(item, idx), marker);
    });
    
    if (linesVisible && lineObjects.length > 0) {
        lineObjects = [];
        showlinebuttonClicked();
    }
}

// 在地图上添加新标记点
function addMarkerToMap(index, data) {
    const point = new T.LngLat(data.longitude, data.latitude);
    const marker = new T.Marker(point);
    map.addOverLay(marker);
    addClickHandler(createInfoContent(data, index), marker);
}

// 搜索功能
function searchbuttonClicked() {
    const keyWord = document.getElementById('keyWord').value;
    map.clearOverLays();
    
    if (typeof lineObjects !== 'undefined') {
        lineObjects.forEach(line => map.removeOverLay(line));
        lineObjects = [];
        linesVisible = false;
    }
    
    const deviceBtn = document.getElementById('device');
    deviceFilterActive = false;
    deviceBtn.style.backgroundColor = '#007bff';
    
    fetch(`phpapi/index.php?title=${encodeURIComponent(keyWord)}`)
        .then(res => res.json())
        .then(response => {
            allDatas = response.data;
            datas = [...allDatas];
            
            if (datas?.length > 0) {
                map.centerAndZoom(new T.LngLat(datas[0].longitude, datas[0].latitude), zoom);
                datas.forEach((item, index) => {
                    const point = new T.LngLat(item.longitude, item.latitude);
                    const marker = new T.Marker(point);
                    map.addOverLay(marker);
                    addClickHandler(createInfoContent(item, index), marker);
                });
            }
        })
        .catch(err => console.error("请求失败:", err));
}

function addClickHandler(content, marker) {
    marker.addEventListener("click", function(e) {
        openInfo(content, e);
    });
}

function openInfo(content, e) {
    const point = e.lnglat;
    const markerInfoWin = new T.InfoWindow(content, {offset: new T.Point(0, -30)});
    map.openInfoWindow(markerInfoWin, point);
    toggleForm(false);
    
    // 保存当前信息窗口状态
    currentInfoWindow = {
        content: content,
        lnglat: point,
        isOpen: true
    };
}

// GPS定位
function gpsbuttonClicked() {
    const lo = new T.Geolocation();
    const fn = function(e) {
        if (this.getStatus() === 0 || this.getStatus() === 1) {
            const marker = new T.Marker(e.lnglat);
            map.centerAndZoom(e.lnglat, zoom);
            map.addOverLay(marker);
        }
    };
    lo.getCurrentPosition(fn);
}

// 地图点击事件
function MapClick(e) {
    const isHidden = formContainer.style.right === '-350px';
    if (isHidden) {
        toggleForm(true);
        clearForm();
        document.getElementById("longitude").value = e.lnglat.getLng();
        document.getElementById("latitude").value = e.lnglat.getLat();
    } else {
        toggleForm(false);
    }
}

// 信息窗口点击显示表单
function infoClicked(i) {
    const isHidden = formContainer.style.right === '-350px';
    const isEmpty = document.getElementById("polename").value === "";
    
    if (isHidden) {
        toggleForm(true);
        fillForm(datas[i]);
    } else if (isEmpty) {
        fillForm(datas[i]);
    } else {
        toggleForm(false);
    }
}

document.getElementById('myForm').addEventListener('submit', function(event) {
    event.preventDefault(); // 阻止表单默认提交行为
    var isValid = this.checkValidity();
    if (!isValid) {
      // 显示错误信息
      var polename = document.getElementById('polename');
      if (!polename.validity.valid) {
        document.getElementById('polename-error').style.display = 'block';
      } else {
        document.getElementById('polename-error').style.display = 'none';
      }

      return false; // 阻止表单提交
    } else {
      // 表单验证通过，可以进行提交或其他操作
      //console.log('表单验证通过，可以提交了！');
      // 这里可以添加代码来处理表单数据，例如发送到服务器
            // 表单验证通过，准备发送数据
            var fileInput = document.getElementById('fileInput');
            var file = fileInput.files[0];
            if (file) {
                uploadImage();
            }  
      var formData = {
        polename: document.getElementById('polename').value,
        upperpole: document.getElementById('upperpole').value,
        disconnectswitch: document.getElementById('disconnectswitch').value,
        circuitbreaker: document.getElementById('circuitbreaker').value,
        bareconductor: document.getElementById('bareconductor').value,
        longitude: document.getElementById('longitude').value,
        latitude: document.getElementById('latitude').value,
        address: document.getElementById('address').value,
        remark: document.getElementById('remark').value,
        //imgurl:"https://gy.ccxk.eu/img/"+document.getElementById('polename').value+".jpg"
        imgurl:document.getElementById('polename').value+".jpg"
        // 添加更多表单字段到对象中
      };
      var jsonData = JSON.stringify(formData); // 将对象转换为JSON字符串
      console.log(jsonData);
      var xhr = new XMLHttpRequest(); // 创建XMLHttpRequest对象
      xhr.open('POST', 'phpapi/post.php', true); // 设置请求方法、URL和异步传输
      xhr.setRequestHeader('Content-Type', 'application/json'); // 设置请求头

      // 检查表单当前是否显示
      const formWasVisible = formContainer.style.right === '0';
      
      xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
          // 请求已完成且状态码为200（成功）
          var response = JSON.parse(xhr.responseText); // 将响应内容转换为JSON对象
          alert(response.data); // 显示响应消息
          
          // 前端同步更新
          if (response.state === 'success') {
            // 获取当前表单数据
            const currentData = {
              PoleName: document.getElementById('polename').value,
              UpperPole: document.getElementById('upperpole').value,
              DisconnectSwitch: document.getElementById('disconnectswitch').value,
              CircuitBreaker: document.getElementById('circuitbreaker').value,
              BareConductor: document.getElementById('bareconductor').value,
              longitude: document.getElementById('longitude').value,
              latitude: document.getElementById('latitude').value,
              Address: document.getElementById('address').value,
              Note: document.getElementById('remark').value,
              ImgUrl: document.getElementById('polename').value + '.jpg'
            };
            
            // 查找是否已存在该杆号的数据
            const existingIndex = datas.findIndex(item => item.PoleName === currentData.PoleName);
            
            if (existingIndex >= 0) {
              // 更新已有数据
              datas[existingIndex] = { ...datas[existingIndex], ...currentData };
              // 更新地图上的标记
              updateMarkerOnMap(existingIndex, currentData);
            } else {
              // 添加新数据到数组
              datas.push(currentData);
              // 在地图上添加新标记
              addMarkerToMap(datas.length - 1, currentData);
            }
            
            // 保持表单显示状态
            if (formWasVisible) {
              toggleForm(true);
            }
            
            // 保持信息窗口显示状态
            if (currentInfoWindow.isOpen && currentInfoWindow.content) {
              // 重建信息窗口内容（使用更新后的数据）
              const polename = document.getElementById('polename').value;
              const updatedData = datas.find(item => item.PoleName === polename);
              if (updatedData) {
                const newContent = `
                    <div style='margin:0px;'>
                        <div style='margin:10px 10px;'>
                            ${getImgTag(updatedData.ImgUrl)}
                            <div style='margin:10px 0px 10px 120px;' onClick='infoClicked(${existingIndex >= 0 ? existingIndex : datas.length - 1})'>
                                杆号：${updatedData.PoleName}<br>
                                上级杆：${updatedData.UpperPole}<br>
                                刀闸：${updatedData.DisconnectSwitch}<br>
                                开关：${updatedData.CircuitBreaker}<br>
                                裸导：${updatedData.BareConductor}<br>
                                经度：${updatedData.longitude}<br>
                                纬度：${updatedData.latitude}<br>
                                地址：${updatedData.Address}<br>
                                备注：${updatedData.Note}
                            </div>
                        </div>
                    </div>`;
                const markerInfoWin = new T.InfoWindow(newContent, {offset: new T.Point(0, -30)});
                map.openInfoWindow(markerInfoWin, currentInfoWindow.lnglat);
                
                // 更新保存的信息窗口内容
                currentInfoWindow.content = newContent;
              }
            }
          }
        }
      };

      xhr.send(jsonData); // 发送请求
    }
  });
  
  function uploadImage() {
    event.preventDefault(); // 阻止表单默认提交行为
    var fileInput = document.getElementById('fileInput');
    var file = fileInput.files[0];
    if (!file) {
        alert('请先选择一个图片文件！');
        return;
    }

    // 读取文件为DataURL
    var reader = new FileReader();
    reader.onload = function(e) {
        var img = new Image();
        img.onload = function() {
            // 图片尺寸限制配置
            var maxWidth = 2048;  // 最大宽度
            var maxHeight = 2048; // 最大高度
            var quality = 0.85;  // 基础质量值

            // 计算新尺寸（等比缩放）
            var newWidth = img.width;
            var newHeight = img.height;
            
            // 如果图片过大，先缩小尺寸
            if (img.width > maxWidth || img.height > maxHeight) {
                var ratio = Math.min(maxWidth / img.width, maxHeight / img.height);
                newWidth = Math.round(img.width * ratio);
                newHeight = Math.round(img.height * ratio);
            }

            // 根据文件大小动态调整质量
            if (file.size > 10 * 1024 * 1024) {
                // 超过10MB：缩小尺寸 + 中等质量
                newWidth = Math.round(newWidth * 0.5);
                newHeight = Math.round(newHeight * 0.5);
                quality = 0.7;
            } else if (file.size > 5 * 1024 * 1024) {
                // 5-10MB：适当缩小 + 较高质量
                newWidth = Math.round(newWidth * 0.7);
                newHeight = Math.round(newHeight * 0.7);
                quality = 0.75;
            } else if (file.size > 2 * 1024 * 1024) {
                // 2-5MB：保持尺寸 + 高质量
                quality = 0.8;
            } else if (file.size > 500 * 1024) {
                // 500KB-2MB：高质量
                quality = 0.85;
            }
            // 500KB以下：最高质量

            // 创建Canvas元素
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            
            // 设置canvas尺寸
            canvas.width = newWidth;
            canvas.height = newHeight;

            // 使用更好的图像平滑算法
            ctx.imageSmoothingEnabled = true;
            ctx.imageSmoothingQuality = 'high';

            // 将图片绘制到Canvas上（带平滑缩放）
            ctx.drawImage(img, 0, 0, newWidth, newHeight);

            // 尝试进行USM锐化处理提升清晰度
            if (file.size > 500 * 1024) {
                try {
                    var imageData = ctx.getImageData(0, 0, newWidth, newHeight);
                    var data = imageData.data;
                    var sharpAmount = 0.2; // 锐化强度
                    
                    // USM锐化 - 对每个通道分别处理
                    for (var y = 1; y < newHeight - 1; y++) {
                        for (var x = 1; x < newWidth - 1; x++) {
                            var idx = (y * newWidth + x) * 4;
                            
                            // 对RGB三个通道分别进行锐化
                            for (var c = 0; c < 3; c++) {
                                var center = data[idx + c];
                                var neighbors = (
                                    data[idx + c - 4] + 
                                    data[idx + c + 4] + 
                                    data[idx + c - newWidth * 4] + 
                                    data[idx + c + newWidth * 4]
                                ) / 4;
                                var diff = center - neighbors;
                                var newValue = center + diff * sharpAmount;
                                data[idx + c] = Math.min(255, Math.max(0, newValue));
                            }
                        }
                    }
                    ctx.putImageData(imageData, 0, 0);
                } catch (e) {
                    // 如果跨域或出错，跳过锐化
                    console.log('图像锐化跳过:', e.message);
                }
            }

            // 获取压缩后的图片数据
            var dataUrl = canvas.toDataURL('image/jpeg', quality);

            // 创建Blob对象
            var blobBin = atob(dataUrl.split(',')[1]);
            var array = [];
            for (var i = 0; i < blobBin.length; i++) {
                array.push(blobBin.charCodeAt(i));
            }
            var blob = new Blob([new Uint8Array(array)], {type: 'image/jpeg'});

            // 创建FormData对象并发送
            var formData = new FormData();
            // 生成新的文件名，例如使用时间戳
            //var newFileName = 'image_' + Date.now() + '.jpg';
            var newFileName = document.getElementById('polename').value + '.jpg';
            formData.append('image', blob, newFileName); // 发送文件，并附带新的文件名
            formData.append('newFileName', newFileName); // 发送新文件名到服务器

            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'phpapi/upload.php', true);

            // 上传进度事件
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percentComplete = (e.loaded / e.total) * 100;
                    document.getElementById('uploadProgress').value = percentComplete;
                }
            };

            // 请求完成事件
            xhr.onload = function() {
                if (xhr.status === 200) {
                    alert('图片上传成功！');
                } else {
                    alert('图片上传失败！');
                }
            };

            // 发送请求
            xhr.send(formData);
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}
//调用高德app导航
function openAmapApp() {
    event.preventDefault();
    
    const endLng = parseFloat(document.getElementById("longitude").value) + 0.00485;
    const endLat = parseFloat(document.getElementById("latitude").value) - 0.00335;
    const endName = document.getElementById("polename").value || '终点';
    
    const amapUrl = `amapuri://route/plan/?dlat=${endLat}&dlon=${endLng}&dname=${encodeURIComponent(endName)}&dev=0&t=0`;
    window.location.href = amapUrl;
}
//线路显示状态追踪
let linesVisible = false;
let lineObjects = [];

//显示线路走向（切换显示/隐藏）
function showlinebuttonClicked() {
    const lineBtn = document.getElementById('line');
    
    if (linesVisible) {
        lineObjects.forEach(line => map.removeOverLay(line));
        lineObjects = [];
        linesVisible = false;
        lineBtn.textContent = '线路';
    } else if (datas && datas.length > 0) {
        datas.forEach(parent => {
            datas.forEach(child => {
                if (parent.PoleName === child.UpperPole) {
                    const points = [
                        new T.LngLat(parent.longitude, parent.latitude),
                        new T.LngLat(child.longitude, child.latitude)
                    ];
                    const line = new T.Polyline(points);
                    // 如果两端都为裸导线，设置线条为红色
                    if (parent.BareConductor && parent.BareConductor.trim() !== '' && 
                        child.BareConductor && child.BareConductor.trim() !== '') {
                        line.setColor('red');
                    }
                    map.addOverLay(line);
                    lineObjects.push(line);
                    
                    const lineLength = Math.ceil(points[0].distanceTo(points[1])) + "米";
                    addLineClickHandler(lineLength, line);
                }
            });
        });
        
        if (lineObjects.length > 0) {
            linesVisible = true;
            lineBtn.textContent = '隐藏线路';
        }
    }
}
function addLineClickHandler(lineLength, line) {
    line.addEventListener("click", function(e) {
        openlineInfo(lineLength, e);
    });
}

function openlineInfo(lineLength, e) {
    const markerInfoWin = new T.InfoWindow(lineLength, {offset: new T.Point(0, -30)});
    map.openInfoWindow(markerInfoWin, e.lnglat);
    formContainer.style.right = '0';
}

// 设备筛选按钮点击事件
function devicebuttonClicked() {
    const deviceBtn = document.getElementById('device');
    
    if (deviceFilterActive) {
        // 恢复显示所有设备
        deviceFilterActive = false;
        deviceBtn.style.backgroundColor = '#007bff';
        datas = [...allDatas]; // 恢复原始数据
    } else {
        // 只显示有刀闸或开关的设备
        deviceFilterActive = true;
        deviceBtn.style.backgroundColor = '#dc3545';
        datas = allDatas.filter(item => 
            (item.DisconnectSwitch && item.DisconnectSwitch.trim() !== '') || 
            (item.CircuitBreaker && item.CircuitBreaker.trim() !== '')
        );
    }
    
    // 重新渲染地图
    renderMarkers();
    
    // 重新显示线路（如果线路可见）
    if (linesVisible && datas.length > 0) {
        lineObjects = [];
        showlinebuttonClicked();
    }
}

// 渲染标记点
function renderMarkers() {
    map.clearOverLays();
    
    if (typeof lineObjects !== 'undefined') {
        lineObjects.forEach(line => map.removeOverLay(line));
        lineObjects = [];
    }
    
    datas.forEach((item, index) => {
        const point = new T.LngLat(item.longitude, item.latitude);
        const marker = new T.Marker(point);
        map.addOverLay(marker);
        addClickHandler(createInfoContent(item, index), marker);
    });
}