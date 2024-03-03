import React, { useState } from "react";
import ReactDOM from "react-dom";

const CopyClipboard = ({ text }) => {
    const [isCopied, setIsCopied] = useState(false);
    const timeout = 1000;

    const copyText = async () => {
        if ("clipboard" in navigator)
            return await navigator.clipboard.writeText(text);
        else return document.execCommand("copy", true, text);
    };

    const handleClick = () => {
        copyText().then(() => {
            setIsCopied(true);
            setTimeout(() => {
                setIsCopied(false);
            }, timeout);
        });
    };

    return (
        <div className="CopyClipboard">
            <div className="row gx-2">
                <div className="col-10">
                    <b className="text-nowrap fs-6">{text}</b>
                </div>
                <div className="col"></div>
                <div className="col-1">
                    <button className="btn btn-sm btn-search-outline float-end" onClick={handleClick}>
                        <span>{isCopied ? <i className="fa-solid fa-check"></i> : <i className="fa-solid fa-copy"></i>}</span>
                    </button>
                </div>
            </div>
        </div>
    );
};

export default CopyClipboard;
