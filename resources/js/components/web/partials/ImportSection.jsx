import React from 'react';
import ReactDOM from 'react-dom';
import ApkInput from './ApkInput';
import AppNamesInput from './AppNamesInput';

const ImportSection = () => {
    return (
        <div className="row">
            <div className='col bg-light rounded-4 mx-2'>
                <ApkInput/>
            </div>
            <div className='col bg-light rounded-4 mx-2'>
                <AppNamesInput/>
            </div>
        </div>
    );
}

export default ImportSection;