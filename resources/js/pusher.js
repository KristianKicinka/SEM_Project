/**
 * @file pusher.js
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

// pusher config file

import Pusher from 'pusher-js';

const pusher = new Pusher('f52ab8a46bdf6ff60c2d', {
  cluster: 'eu',
});

export default pusher;
