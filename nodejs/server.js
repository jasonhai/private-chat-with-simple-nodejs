// var options = {
//     ca: fs.readFileSync('/usr/local/ssl/certificate/cabundle.crt'),
//     cert: fs.readFileSync('/usr/local/ssl/certificate/gogtour.com.crt'),
//     key: fs.readFileSync('/usr/local/ssl/certificate/gogtour.com.key')
// };
var app = require('express')();
var fs = require('fs');
var redis = require('redis');
var https = require('https');
// var privateKey  = fs.readFileSync('/usr/local/ssl/certificate/gogtour.com.key').toString();
// var certificate = fs.readFileSync('/usr/local/ssl/certificate/gogtour.com.crt').toString();
// var credentials = {key: privateKey, cert: certificate};

var port = '8890';
sequence = 1;
usernames = [];

// var server = https.createServer(credentials, app);
var server = https.createServer(app);
var io = require('socket.io')(server);

// app.get('/', function(req, res) {
// res.sendfile('/');
// });

server.listen(port);

io.on('connection', function (socket) {
    console.info('New client connected (id=' + socket.id + ').');
    usernames.push(socket.id);

    var redisClient = redis.createClient();
    redisClient.on("message", function(channel, message) {
        console.log("New message: " + message + ". In channel: " + channel);
        console.log('Room: '+socket.room, socket.rooms);

        io.sockets.in(socket.room).emit('newMessage', message);
    });

    socket.on('room', function  (data) {
        console.log("join room", data.room);
        socket.join(data.room);
        socket.room = data.room;
        socket.channel = data.channel;
        redisClient.subscribe('notification' + data.channel)
    })

    // When socket disconnects, remove it from the list:
    socket.on('disconnect', function() {
        var index = usernames.indexOf(socket);
        if (index != -1) {
            usernames.splice(index, 1);
            console.info('Client gone (id=' + socket.id + ').');
        }
        redisClient.unsubscribe('notification' + socket.channel);
        socket.leave(socket.room);
    });

    socket.on('disconnect', function() {
        redisClient.quit();
    });

});

