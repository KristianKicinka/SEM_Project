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
            <div className="d-flex">
                <b className="text-nowrap fs-6 flex-fill px-2">{text}</b>
                <button className="btn btn-sm btn-search-outline" onClick={handleClick}>
                    <span>{isCopied ? <i className="fa-solid fa-check"></i> : <i className="fa-solid fa-copy"></i>}</span>
                </button>
            </div>
        </div>
    );
};

export default CopyClipboard;
