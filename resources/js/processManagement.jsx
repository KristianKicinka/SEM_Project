import { v4 as uuidv4 } from 'uuid';

const createNewProcessID = () => {
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


