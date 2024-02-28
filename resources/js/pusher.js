// pusher.js
import Pusher from 'pusher-js';

const pusher = new Pusher('f52ab8a46bdf6ff60c2d', {
  cluster: 'eu',
});

export default pusher;
