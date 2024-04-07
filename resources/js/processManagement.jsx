import { v4 as uuidv4 } from 'uuid';

const createNewProcessID = () => {
    const uniqueId = uuidv4().substring(0,23);
    return `int_api_${uniqueId}`;
}

const createNewChannelID = () => {
    return uuidv4().substring(0,8);
}

export const setNewActiveProcess = () => {
    const processID = createNewProcessID();
    let activeProcesses = getActvieProcesses();
    
    activeProcesses.push(processID);
    localStorage.setItem('ActiveProcesses', JSON.stringify(activeProcesses));

    return processID;
}

export const getActvieProcesses = () => {
    return JSON.parse(localStorage.getItem('ActiveProcesses'));
}

export const setNewActiveChannel = () => {
    const channelID = createNewChannelID();
    let activeChannels = getActvieProcesses();
    
    activeChannels.push(channelID);
    localStorage.setItem('ActiveChannels', JSON.stringify(activeChannels));

    return channelID;
}

export const getActvieChannels = () => {
    return JSON.parse(localStorage.getItem('ActiveChannels'));
}




