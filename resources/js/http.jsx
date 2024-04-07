/**
 * @file http.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import axios from "axios";

export default axios.create({
    baseURL: `/`,
    headers: {
        "Content-type" : "application/json",
    }

});