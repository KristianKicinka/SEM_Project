/**
 * @file processManagement.jsx
 * @author Kristián Kičinka (xkicin02)
 * 
 * @copyright Copyright (c) 2024
 */

import { v4 as uuidv4 } from 'uuid';

/**
 * @brief The function ensures process id creation
 * @returns New process ID
 */
const createNewProcessID = () => {
    const uniqueId = uuidv4().substring(0,23);
    return `cl_api_${uniqueId}`;
}

/**
 * @brief The function ensures channel id creation
 * @returns New channel ID
 */
const createNewChannelID = () => {
    return uuidv4().substring(0,8);
}

/**
 * @brief The function ensures setting new active process
 * @returns Process ID
 */
export const setNewActiveProcess = () => {
    const processID = createNewProcessID();
    let activeProcesses = getActvieProcesses();
    
    activeProcesses.push(processID);
    localStorage.setItem('ActiveProcesses', JSON.stringify(activeProcesses));

    return processID;
}

/**
 * @brief The function ensures getting active processes
 * @returns Active processes
 */
export const getActvieProcesses = () => {
    return JSON.parse(localStorage.getItem('ActiveProcesses'));
}

/**
 * @brief The function ensures setting new active channel
 * @returns Channel ID
 */
export const setNewActiveChannel = () => {
    const channelID = createNewChannelID();
    let activeChannels = getActvieProcesses();
    
    activeChannels.push(channelID);
    localStorage.setItem('ActiveChannels', JSON.stringify(activeChannels));

    return channelID;
}

/**
 * @brief The function ensures getting active channels
 * @returns Active channels
 */
export const getActvieChannels = () => {
    return JSON.parse(localStorage.getItem('ActiveChannels'));
}




