// Require HTTP module (to start server) and Socket.IO
var app = require('express')();
var server = require('http').Server(app);
var io = require('socket.io')(server);
var redis = require('redis');

var port = '8890';
usernames = [];

// Start the server at port 8080
// var server = http.createServer(function(req, res){ 

//     // Send HTML headers and message
//     res.writeHead(200,{ 'Content-Type': 'text/html' }); 
//     res.end('<h1>Hello Socket Lover!</h1>');
// });
// server.listen(port);

// var server = http.createServer(app);
var io = require('socket.io');

// Create a Socket.IO instance, passing it our server
var socket = io.listen(server);


server.listen(port, function () {
    console.log('Express server listening on port %d in %s mode', port, app.get('env'));
});

socket.on('connection', function (socket) {
    console.info('New client connected (id=' + socket.id + ').');
    usernames.push(socket.id);

    var redisClient = redis.createClient();
    redisClient.on("message", function(channel, message) {
        console.log("New message: " + message + ". In channel: " + channel);
        console.log('Room: '+socket.room, socket.rooms);

        io.sockets.in(socket.room).emit('newMessage', message);
    });

    socket.on('room', function  (data) {
        console.log("Join room", data.room);
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

